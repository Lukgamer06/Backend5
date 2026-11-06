# Revisión de rendimiento — HostalConnect UNAB v1.0

**Fase:** 6 — Optimización
**Fecha:** 2026-11-05
**Alcance:** consultas SQL, eager loading, paginación, carga de API y configuración Docker existente.

## Hallazgo y optimización aplicada

La búsqueda de mesas disponibles cargaba todas las mesas y ejecutaba `hasConflict()` una vez por cada una. Con `N` mesas, el endpoint emitía `1 + N` consultas de lectura: una consulta a `mesas` y hasta `N` consultas `exists` contra `reservas`.

La consulta se trasladó al repositorio: filtra estado operativo y capacidad y excluye mediante `whereDoesntHave` las mesas con reservas solapadas no canceladas. La selección queda en una consulta SQL, sin cambiar el orden ni los criterios del resultado.

| Métrica | Antes | Después |
|---|---:|---:|
| Lecturas para disponibilidad con 20 mesas candidatas | 21 | 1 |
| Reducción de sentencias/round trips para ese caso | — | 20 menos; ~95% |
| Número de tablas cargadas antes del filtrado en PHP | Todas | Solo las coincidentes |

El impacto estimado es menor latencia y menos trabajo de CPU/DB para catálogos de mesas medianos o grandes. El porcentaje cuantificado corresponde al número de sentencias, no a una medición de latencia: no se ejecutó una prueba de carga ni un benchmark de producción.

Se agregó una prueba de regresión que verifica que 20 mesas disponibles se obtienen con una consulta. Las pruebas existentes también comprueban capacidad, mantenimiento y conflictos.

## Revisión complementaria

- **Eager loading:** las listas de reservas/pedidos/incidencias y menús usan `with(...)` para relaciones consumidas en colecciones o recursos; no se identificó un N+1 comparable en las vistas revisadas.
- **Paginación:** las listas principales de dominio usan paginación. Los catálogos de opciones para formularios y las listas de mesas completas son usos acotados del sistema actual; no se cambió su UX ni contrato.
- **Dashboard:** carga conjuntos recientes con límites y resuelve la actividad diaria de forma agregada en SQL; sin mediciones de producción no se justifican más consultas/cachés.
- **CPU/RAM:** no se recolectaron perfiles ni picos de uso. La optimización aplicada reduce consultas repetidas; no se afirma reducción absoluta de CPU/RAM medida.
- **API:** se mantienen contratos, filtros y paginación. No se modificaron respuestas ni se añadió caché.
- **Docker:** Compose local mantiene Nginx, PHP-FPM y MySQL separados con healthchecks y volúmenes persistentes. Se conserva la configuración para no alterar despliegue/comportamiento en esta fase. Para un perfil de producción, valorar imagen inmutable, OPcache y límites/observabilidad de recursos durante la preparación de despliegue.

## Validación

- `php artisan test tests/Feature/ReservationsTest.php`: 11 pruebas aprobadas, 64 assertions.
- `php artisan test tests/Feature/Api/V1/ReservationsApiTest.php`: 11 pruebas aprobadas, 74 assertions.
- `vendor/bin/pint --test` sobre los archivos PHP afectados: aprobado.
- La suite completa tuvo una ejecución aprobada (137 pruebas, 865 assertions) y otras ejecuciones con fallos intermitentes en filtros de pedidos o PATCH de reservas. `OrdersTest` pasó en una ejecución aislada y falló en otra; el PATCH pasó en una ejecución aislada y la suite API de reservas también pasó por separado. Los filtros/validadores de esos casos no fueron modificados. No se considera estable la suite completa; revisar determinismo de datos/fechas en el seguimiento de testing.
- No se ejecutó benchmark, prueba de concurrencia, ni perfil CPU/RAM.

## Archivos afectados

- `app/Repositories/ReservationRepository.php`: consulta relacional filtrada de mesas disponibles.
- `app/Services/AvailabilityService.php`: delega la lectura optimizada al repositorio.
- `tests/Feature/ReservationsTest.php`: aserción de una consulta para 20 mesas.
- Este informe.

## Decisión de fase

- **Resultado:** N+1 en disponibilidad eliminado con pruebas de comportamiento y conteo de consultas.
- **Modelo de datos, contratos API y dependencias:** sin cambios.
- **Siguiente paso:** detenerse aquí. La fase 7 no se inicia hasta revisar y resolver el control de versiones de esta fase.
