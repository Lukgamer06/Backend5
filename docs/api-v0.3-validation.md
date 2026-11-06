# Contrato y validación de API

Este documento conserva la revisión del contrato API iniciada en v0.3 y se actualizó para la entrega v1.0. Las rutas implementadas se verificaron de nuevo durante la integración; los resultados y límites de las pruebas están diferenciados por etapa abajo.

## Alcance y entorno

- Base de rutas: `/api/v1`.
- Autenticación: tokens personales de Laravel Sanctum.
- Respuestas de éxito: API Resources y colecciones JSON.
- Envoltorio de recursos: `data`, `message`, `meta`.
- Envoltorio de colecciones: `data`, `links`, `meta`, `message`.
- Colección para pruebas manuales: [HostalConnect-UNAB-API-v1.postman_collection.json](./postman/HostalConnect-UNAB-API-v1.postman_collection.json).
- Entorno local Postman: [HostalConnect-UNAB-Local.postman_environment.json](./postman/HostalConnect-UNAB-Local.postman_environment.json).

## Checklist técnico

- [x] Rutas versionadas declaradas bajo `/api/v1`.
- [x] Login público con throttling; rutas privadas agrupadas con `auth:sanctum`.
- [x] Tokens personales almacenados mediante Sanctum y revocación del token actual al cerrar sesión.
- [x] Controllers API utilizan Form Requests para entradas validadas y Policies/Gate para autorización.
- [x] Recursos individuales usan API Resources; las listas usan Resource Collections.
- [x] Persistencia y consultas delegadas a Services y Repositories.
- [x] Ownership de clientes aplicado a consultas y acceso individual de reservas y pedidos.
- [x] El estado, usuario reportante, total de pedido y datos de pago controlados por servidor no se aceptan como entrada manipulable.
- [x] Pagos idempotentes; la API no simula aprobación ni integra una pasarela.
- [x] Filtros y límites de paginación validados.
- [x] Errores estandarizados para 401, 403, 404, 405, 409, 422 y 429; los errores inesperados API responden 500 sin exponer detalles internos.
- [x] Rutas registradas verificadas con `php artisan route:list --path=api/v1`.
- [x] Respuestas y permisos cubiertos por pruebas feature API.
- [x] Colección y entorno Postman en formato JSON válidos; la colección cubre las 29 rutas API registradas e incluye respuestas de ejemplo.
- [x] No hay endpoint REST `DELETE` en el alcance de v0.3. Un `DELETE /api/v1/reservas/{id}` no soportado responde 405 estandarizado. No se genera respuesta 204 porque ningún endpoint actual realiza borrado.

## Checklist funcional

- [x] Login correcto devuelve token y datos públicos del usuario; credenciales inválidas devuelven 401 y entradas inválidas devuelven 422.
- [x] Logout revoca solo el token actual; `GET /me` devuelve el perfil autenticado sin devolver el token.
- [x] Reservas: listado paginado y filtrable, detalle con control de ownership, creación, actualización parcial y cancelación.
- [x] Disponibilidad de mesas consultable por horario y aforo; solo devuelve mesas habilitadas, con capacidad suficiente y sin reservas conflictivas. La creación vuelve a validar disponibilidad y los conflictos devuelven 409.
- [x] Pedidos: listado paginado y filtrable, detalle, creación con total calculado en servidor, actualización, cancelación y transición de estado.
- [x] Acciones de pedido respetan ownership, rol y transiciones; transiciones no permitidas devuelven 409.
- [x] Catálogo: listado paginado, búsqueda y detalle; usuarios no administradores no ven productos inactivos.
- [x] Menú del día: consulta únicamente el menú publicado de hoy; personal puede crear/actualizar borradores y publicar menús no vacíos.
- [x] Pagos: consulta por ID y pedido; registro presencial/en línea mantiene `PENDIENTE`, toma el monto del pedido y aplica idempotencia.
- [x] Incidencias: listado filtrable y paginado, creación con usuario reportante asignado desde la sesión y actualización parcial de estado/prioridad.
- [x] Los accesos no autenticados, no autorizados y a recursos faltantes se responden con los códigos JSON normalizados.

## Verificación de contrato (v0.3)

Comandos de validación:

```bash
php artisan route:list --path=api/v1
php artisan test
vendor/bin/pint --test
git diff --check
```

Entorno de ejecución del proyecto durante esta validación: contenedor `hostalconnect-v02-phase4-app` con el volumen de dependencias `hostalconnect-unab_vendor`.

Resultado final:

- 28 rutas `/api/v1` registradas en la revisión original de v0.3. En el código actual se verifican 29 rutas.
- 135 pruebas aprobadas (850 assertions).
- Pint aprobado.
- `git diff --check` aprobado.
- JSON de colección y entorno Postman parseados y validados.
- Cobertura de métodos HTTP: GET, POST y PATCH según endpoints soportados; DELETE verificado como 405 al no existir endpoint de borrado.

## Estado actual v1.0

- La colección Postman contiene 29 solicitudes para login/logout/me, incidencias, reservas, pedidos, pagos, productos y menús del día. Configura una cuenta local válida y `baseUrl` como `http://localhost:8080` para la configuración Docker predeterminada.
- La API sigue protegida con Sanctum en las rutas privadas; las respuestas de error API se normalizan y los recursos de reservas/pedidos aplican ownership según rol.
- Durante la fase de integración se ejecutaron 136 pruebas Laravel con 863 assertions y un flujo HTTP real contra Laravel/MySQL: login, lecturas, disponibilidad, crear/leer/cancelar reserva, denegación 403, validación 422, logout y rechazo del token revocado con 401.
- La suite automatizada Laravel usa SQLite en memoria. La prueba HTTP de integración no equivale a certificar toda la API ni el cliente móvil en producción.
- El cliente Flutter ya implementa configuración por ambiente, almacenamiento seguro de token, autenticación, manejo de respuestas/errores y los flujos descritos en [mobile/README.md](../mobile/README.md).
- La verificación Flutter registrada por CI fue 72 pruebas aprobadas y 1 omitida, condicionada a configuración de staging. No se ejecutó el binario Flutter contra una API/MySQL viva.
- La colección Postman se valida estructuralmente; no sustituye pruebas de autorización con cuentas de todos los roles ni una ejecución completa de cada solicitud con fixtures válidas.

Los resultados de rendimiento y la intermitencia observada en algunos tests están detallados en [performance-review-v1.0.md](./performance-review-v1.0.md) y [integration-test-report-v1.0.md](./integration-test-report-v1.0.md). No interpretes una prueba unitaria o de contrato como integración de extremo a extremo.
