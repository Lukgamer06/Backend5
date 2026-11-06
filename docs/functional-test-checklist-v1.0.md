# Validación funcional — HostalConnect UNAB v1.0

**Fase:** 4 — Pruebas funcionales
**Fecha:** 2026-11-05
**Alcance:** pruebas funcionales existentes para web, API REST y cliente Flutter. No se agregan módulos ni funcionalidades.

## 1. Resultado de ejecución disponible

| Superficie | Resultado | Evidencia y límites |
|---|---|---|
| Flutter | Aprobado en CI para el commit remoto `ef93d12182706744e20602e40e9b11e008422725` (`feature/v0.4-flutter-client`) el 2026-11-06. `flutter analyze --no-pub`: sin problemas; `flutter test --no-pub`: 72 aprobadas y 1 omitida. | [Ejecución Mobile CI](https://github.com/Lukgamer06/Backend5/actions/runs/42154545387). La prueba omitida es `live_api_smoke_test.dart`, que requiere API staging y credenciales configuradas. El commit local actual incluye commits posteriores de documentación/revisión; no se publicó esta fase para CI. |
| Laravel web | No ejecutado en esta sesión. | PHP y Composer no están disponibles; `vendor/` no contiene dependencias. El repositorio incluye pruebas Feature para autenticación, dashboard y módulos. |
| Laravel API | No ejecutado en esta sesión. | No se pudo levantar Laravel ni MySQL localmente. Existen suites Feature API por recurso y autenticación. |
| Integración Flutter → API → Laravel → MySQL | No ejecutado contra staging en esta sesión. | El smoke test está condicionado por `LIVE_API_BASE_URL`, `LIVE_API_EMAIL`, `LIVE_API_PASSWORD` y `APP_ENV=staging`; no se configuraron ni se enviaron credenciales. |

El historial documental de la validación API v0.3 reportó anteriormente 135 pruebas y 850 assertions aprobados. Se considera evidencia histórica, no una repetición de pruebas de esta fase.

## 2. Checklist funcional web

Las rutas web están cubiertas por pruebas feature en `tests/Feature/`:

- [x] **Login:** credenciales correctas/incorrectas, sesión, remember-me y logout — `AuthenticationTest.php`.
- [x] **Dashboard:** métricas por rol, registros recientes, privacidad de datos de pagos e incidencias y estados vacíos — `DashboardTest.php`, `DashboardQueryServiceTest.php`.
- [x] **Reservas:** acceso de invitado, CRUD, disponibilidad/conflictos, ownership de cliente, permisos y filtros — `ReservationsTest.php`.
- [x] **Pedidos:** creación/actualización/cancelación, detalles, ownership, transiciones de estado, fechas y restricciones de pedidos pagados — `OrdersTest.php`.
- [x] **Productos:** CRUD, disponibilidad, activación/desactivación, búsquedas, paginación y autorización por rol — `ProductsTest.php`.
- [x] **Menú:** borradores, publicación, unicidad de fecha, productos disponibles y consulta según rol — `MenuDiaTest.php`.
- [x] **Pagos:** historial, filtros y permisos — `PaymentsTest.php`.
- [x] **Incidencias:** gestión, filtros y permisos — `IncidentsTest.php`.
- [x] **Experiencia web:** mensajes, accesibilidad y estados de interfaz cubiertos — `UiExperienceTest.php`.
- [ ] **Ejecución local actual:** pendiente; el entorno carece de runtime PHP/Composer y dependencias de Laravel.

Las casillas marcadas indican cobertura identificada en el código de pruebas, no una ejecución local en esta sesión.

## 3. Checklist funcional API

Las suites `tests/Feature/Api/V1/` contienen cobertura para:

- [x] **Autenticación/tokens:** login, credenciales erróneas, validación 422, perfil, rutas sin token, rate limit, logout y revocación del token actual — `AuthApiTest.php`.
- [x] **Requests y recursos:** contratos de validación y forma de respuestas — `ApiFormRequestsTest.php`, `ApiResourcesTest.php`.
- [x] **Reservas:** endpoints, disponibilidad, filtros/paginación, ownership, permisos y conflictos — `ReservationsApiTest.php`.
- [x] **Pedidos:** endpoints, ownership, total calculado por servidor, datos manipulados, transiciones, permisos y 404 — `OrdersApiTest.php`.
- [x] **Catálogo y menú:** lectura de productos, estados visibles, menú publicado y operaciones protegidas — `CatalogAndMenuApiTest.php`.
- [x] **Pagos:** creación idempotente, consulta y permisos — `PaymentsApiTest.php`.
- [x] **Incidencias:** consulta, creación, cambios y autorización — `IncidentsApiTest.php`.
- [ ] **Ejecución local actual y comprobación de rutas en runtime:** pendientes por falta de PHP/Composer y base de datos configurada.

## 4. Checklist funcional Flutter

- [x] **Login/sesión:** autenticación, restauración de sesión, cierre y sesión expirada — `features/auth/auth_flow_test.dart`, `features/auth/auth_repository_test.dart`.
- [x] **Transporte API:** formato Bearer, serialización JSON, errores 401/422/500, timeout y retry-after — `core/network/api_client_test.dart`.
- [x] **Configuración:** URL por ambiente, HTTPS en staging/producción y rechazo de hosts inválidos — `core/network/api_config_test.dart`.
- [x] **Lecturas:** catálogo/menú, pedidos, reservas y pagos cubiertos por pruebas de repositorio/controlador.
- [x] **Escrituras:** crear pedidos, reservas, incidencias, menús y solicitudes de pago cubiertas por pruebas de flujo/repositorio/controlador.
- [ ] **API remota en vivo:** no validada en esta sesión; smoke test omitido por requerir staging/credenciales.
- [x] **Análisis y suite local-equivalente en CI:** 72 aprobadas, 1 omitida en el SHA remoto registrado arriba.

## 5. Checklist para ejecución previa a sustentación

- [ ] Ejecutar en un entorno con dependencias instaladas: `composer test`.
- [ ] Ejecutar `vendor/bin/pint --test`.
- [ ] Ejecutar `cd mobile && flutter pub get --enforce-lockfile && flutter analyze --no-pub && flutter test --no-pub`.
- [ ] Proveer credenciales de una cuenta cliente de pruebas mediante configuración segura, no en archivos rastreados, y ejecutar el smoke test de staging.
- [ ] Confirmar en staging login, `/me`, catálogo, menú, pedidos, reservas, disponibilidad y respuestas 401/403/404/422.
- [ ] Revisar evidencia de CI asociada al commit que contenga este checklist.

## 6. Cierre de fase

- **Problemas funcionales nuevos demostrados:** ninguno.
- **Correcciones de comportamiento:** ninguna; esta fase no reveló un fallo nuevo que justificara alterar funcionalidades.
- **Archivos modificados:** únicamente este checklist.
- **Limitación principal:** las pruebas PHP/API y la integración real no se ejecutaron localmente; el resultado Flutter se refiere al SHA remoto documentado, no a una ejecución del HEAD local.
- **Siguiente paso:** detenerse al finalizar esta fase. No iniciar integración ni optimización sin revisión y confirmación de los commits/push según el flujo acordado.
