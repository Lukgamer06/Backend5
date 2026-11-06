# Informe final de cierre — HostalConnect UNAB v1.0

**Fecha de revisión:** 2026-11-05  
**Rama revisada:** `feature/v0.4-flutter-client`  
**Tipo de cierre:** auditoría final de entrega académica; no es certificación de producción.

## Resumen ejecutivo

HostalConnect UNAB reúne una aplicación web Laravel, una API REST autenticada con Sanctum y un cliente Flutter para los flujos existentes de reservas, pedidos, catálogo/menú, pagos e incidencias. La revisión de arquitectura y seguridad, los informes de pruebas, los contratos API/Postman y la documentación de despliegue y sustentación quedan reunidos en el repositorio. No se agregaron módulos ni se cambió el dominio o esquema de datos durante este cierre.

En la validación del cierre se encontraron dos fuentes de no determinismo en fixtures PHP: el fixture de una reserva podía exceder la capacidad aleatoria de su mesa; el test de filtros de pedidos buscaba por un ID numérico que podía aparecer en correos ficticios. Se corrigieron de forma localizada en los tests. Después de esos cambios, la suite Laravel completa pasó dos veces consecutivas: **137 pruebas y 865 assertions por ejecución**; Pint pasó para los archivos PHP afectados. Laravel reportó 29 rutas API registradas y la colección Postman cubre esas 29 rutas.

**Decisión de release:** el cierre documental y las verificaciones locales están completados, pero no se declara una publicación oficial `v1.0` ni certificación productiva. No se ejecutó Flutter real contra el backend live en este cierre, no se verificó CI para el SHA final, no hay infraestructura productiva validada y la rama local aún no se publicó. Por estos motivos no se creó ni publicó tag `v1.0` ni rama `release/v1.0`.

## Matriz de cierre

| Área | Estado | Evidencia actual | Límite |
|---|---|---|---|
| Arquitectura | Revisada | Auditoría de capas, relaciones, rutas y estructura de Flutter; README actualizado con diagrama. | No hay un documento de arquitectura de referencia previo en el repositorio; se contrastó con la arquitectura exigida para esta entrega. |
| Web | Suite automatizada aprobada | Tests Laravel incluyen autenticación, dashboard y módulos web; la suite completa pasó 137/865 dos veces tras estabilizar fixtures. | No se realizó una nueva demostración manual en navegador durante este cierre. |
| API | Verificada estructural y funcionalmente | 29 rutas activas; pruebas Feature y colección Postman mapeada 29/29. | Postman se comprobó estructuralmente; no se ejecutaron manualmente las 29 solicitudes con fixtures de cada rol durante este cierre. |
| Flutter | Parcial | El cliente implementa los flujos descritos y existe CI reportado con 72 pruebas aprobadas y 1 omitida en el SHA remoto documentado. | El host no tiene Flutter/Dart; no se ejecutó contra el SHA local final ni se conectó el binario Flutter a API/MySQL live. La fecha futura indicada en el informe de CI debe confirmarse antes de presentarla como evidencia fechada. |
| Base de datos | Parcialmente verificada | La suite Laravel usa SQLite en memoria; el informe de integración registra un flujo HTTP acotado contra Laravel/MySQL con lecturas, escritura, permisos y revocación. | No se repitió integración MySQL completa en esta fase y no se probó carga/concurrencia. |
| Seguridad | Revisión estática cerrada | Auditoría estática no encontró vulnerabilidades demostrables en las superficies revisadas; rutas, Sanctum, roles, ownership y validaciones están cubiertos por pruebas. | No es pentest ni certificación; no hubo análisis dinámico externo o sobre una instalación productiva. |
| Documentación | Actualizada | README, API/Postman, auditorías, checklist de despliegue y checklist de sustentación. | Deben actualizarse resultados/estado remoto al momento de publicar. |
| Docker/despliegue | Preparación documentada | `docker compose config --quiet` pasa; checklist documenta variables, TLS, backups, logs, salud, scheduler y colas. | Compose es para desarrollo local. No hay despliegue productivo, TLS, backup restaurado ni observabilidad certificados. |
| Git/release | No publicado | Commits locales por fases; árbol de trabajo verificado limpio antes del commit de informe. | Rama actual es `feature/v0.4-flutter-client`; no se hizo push ni existe tag `v1.0` confirmado. El estado debe revisarse tras este commit. |
| Sustentación | Preparada documentalmente | Flujo de demo, casos, checklists y preguntas/respuestas en el checklist de sustentación. | Cuentas, fixtures y ambiente de demo aún deben prepararse y ensayarse. |

## Verificaciones ejecutadas en el cierre

- Suite completa Laravel, mediante `docker compose run --rm --no-deps app php artisan test`: **137 pruebas, 865 assertions**, dos ejecuciones consecutivas aprobadas después de corregir fixtures.
- Pruebas focalizadas de reservas API y pedidos: **21 pruebas, 149 assertions** aprobadas.
- Pint en los archivos PHP modificados: aprobado.
- `php artisan route:list --path=api/v1`: 29 rutas.
- Colección Postman: 29 solicitudes; mapeo exacto de método y ruta con las 29 rutas actuales.
- `docker compose config --quiet`: aprobado.
- `git diff --check` y enlaces Markdown locales: aprobados en los cambios documentales revisados.
- No se ejecutaron comandos que imprimieran los valores de `.env`; el archivo permanece local e ignorado por Git.

La integración MySQL previamente documentada se limitó a un flujo HTTP acotado. No debe sumarse a la suite SQLite como si fuera una sola prueba end-to-end del cliente Flutter.

## Resumen de artefactos de las fases

- Arquitectura: [architecture-audit-v1.0.md](./architecture-audit-v1.0.md)
- Seguridad: [security-audit-v1.0.md](./security-audit-v1.0.md)
- Funcional: [functional-test-checklist-v1.0.md](./functional-test-checklist-v1.0.md)
- Integración: [integration-test-report-v1.0.md](./integration-test-report-v1.0.md)
- Rendimiento: [performance-review-v1.0.md](./performance-review-v1.0.md)
- API y Postman: [api-v0.3-validation.md](./api-v0.3-validation.md)
- Despliegue: [deployment-checklist-v1.0.md](./deployment-checklist-v1.0.md)
- Sustentación: [defense-checklist-v1.0.md](./defense-checklist-v1.0.md)

## Lecciones aprendidas

1. Una prueba verde aislada no demuestra estabilidad de la suite completa; se deben repetir suites sensibles a datos aleatorios y límites de fecha.
2. Los fixtures deben respetar invariantes del dominio (por ejemplo, personas por debajo de capacidad de mesa) y utilizar valores de búsqueda únicos.
3. La cobertura de código, la ejecución local de CI, la integración HTTP con MySQL y el uso del cliente móvil live son evidencias diferentes y deben reportarse separadamente.
4. Mantener el backend como autoridad para ownership, roles, validación y transiciones evita depender de controles de interfaz.
5. La optimización relacional de disponibilidad reduce consultas, pero el conteo de sentencias no reemplaza un benchmark de latencia, carga, CPU o memoria.
6. La documentación debe reflejar las rutas y configuración reales: la auditoría detectó README y pendientes de Flutter obsoletos, además de un `baseUrl` Postman desalineado con Compose.
7. Compose local, volúmenes persistentes y healthchecks no equivalen a arquitectura productiva, backups ni recuperación probada.

## Deuda técnica y puertas pendientes

### Antes de anunciar la publicación v1.0

- [ ] Revalidar la rama/HEAD exactos, estado limpio y commits locales; sincronizar con el repositorio remoto según el flujo acordado.
- [ ] Ejecutar/consultar CI correspondiente al SHA que se publicará y reconciliar la fecha de ejecución Flutter documentada.
- [ ] Preparar staging y correr `live_api_smoke_test.dart` con cuenta dedicada; probar el binario Flutter contra API/MySQL real.
- [ ] Preparar datos de demo desechables y ensayar el flujo de sustentación.
- [ ] Decidir explícitamente si se crea rama `release/v1.0` y tag `v1.0`, y confirmar push del branch/tag.

### Antes de producción (no certificado)

- [ ] Definir hosting, imagen de release inmutable, TLS y proxy confiable.
- [ ] Custodiar secretos fuera de Git; `APP_ENV=production` y `APP_DEBUG=false`.
- [ ] Configurar logs/alertas/monitorización y límites de recursos.
- [ ] Configurar backups cifrados fuera del host y demostrar restauración con RPO/RTO.
- [ ] Validar migraciones, estrategia de release y rollback en un ambiente representativo.
- [ ] Realizar pruebas autorizadas de seguridad, carga y recuperación según criticidad.

### Seguimiento técnico

- [ ] Volver a ejecutar suite y Pint en el commit final; investigar cualquier fallo que reaparezca.
- [ ] Mantener pruebas de fixtures con invariantes consistentes; el test order filter usa un nombre determinista y la reserva de prueba usa capacidad compatible.
- [ ] Añadir mediciones reproducibles de latencia/throughput y perfil CPU/RAM solo cuando exista un ambiente de prueba representativo.
- [ ] Crear un documento de arquitectura de referencia aprobado si el equipo necesita contrastar futuras modificaciones contra un baseline formal.

## Conclusión

La entrega cuenta con evidencia significativa de arquitectura, funciones web/API, documentación y pruebas Laravel, y las dos fuentes de fixtures no deterministas encontradas en el cierre se corrigieron y verificaron con ejecuciones completas repetidas. La evidencia disponible no demuestra una validación total de Flutter live, una certificación productiva ni una publicación remota. El proyecto queda preparado para la sustentación académica con esas limitaciones declaradas; la promoción oficial a tag/rama `v1.0` queda condicionada a las puertas anteriores.
