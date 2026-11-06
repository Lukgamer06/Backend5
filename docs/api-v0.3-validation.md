# Validación final de API v0.3

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
- [x] Colección y entorno Postman en formato JSON válidos; cubren las 27 rutas registradas e incluyen respuestas de ejemplo.
- [x] No hay endpoint REST `DELETE` en el alcance de v0.3. Un `DELETE /api/v1/reservas/{id}` no soportado responde 405 estandarizado. No se genera respuesta 204 porque ningún endpoint actual realiza borrado.

## Checklist funcional

- [x] Login correcto devuelve token y datos públicos del usuario; credenciales inválidas devuelven 401 y entradas inválidas devuelven 422.
- [x] Logout revoca solo el token actual; `GET /me` devuelve el perfil autenticado sin devolver el token.
- [x] Reservas: listado paginado y filtrable, detalle con control de ownership, creación, actualización parcial y cancelación.
- [x] Disponibilidad de mesa y conflicto de reserva gestionados por `AvailabilityService`; los conflictos devuelven 409.
- [x] Pedidos: listado paginado y filtrable, detalle, creación con total calculado en servidor, actualización, cancelación y transición de estado.
- [x] Acciones de pedido respetan ownership, rol y transiciones; transiciones no permitidas devuelven 409.
- [x] Catálogo: listado paginado, búsqueda y detalle; usuarios no administradores no ven productos inactivos.
- [x] Menú del día: consulta únicamente el menú publicado de hoy; personal puede crear/actualizar borradores y publicar menús no vacíos.
- [x] Pagos: consulta por ID y pedido; registro presencial/en línea mantiene `PENDIENTE`, toma el monto del pedido y aplica idempotencia.
- [x] Incidencias: listado filtrable y paginado, creación con usuario reportante asignado desde la sesión y actualización parcial de estado/prioridad.
- [x] Los accesos no autenticados, no autorizados y a recursos faltantes se responden con los códigos JSON normalizados.

## Verificación ejecutada

Comandos de validación:

```bash
php artisan route:list --path=api/v1
php artisan test
vendor/bin/pint --test
git diff --check
```

Entorno de ejecución del proyecto durante esta validación: contenedor `hostalconnect-v02-phase4-app` con el volumen de dependencias `hostalconnect-unab_vendor`.

Resultado final:

- 27 rutas `/api/v1` registradas.
- 132 pruebas aprobadas (829 assertions).
- Pint aprobado.
- `git diff --check` aprobado.
- JSON de colección y entorno Postman parseados y validados.
- Cobertura de métodos HTTP: GET, POST y PATCH según endpoints soportados; DELETE verificado como 405 al no existir endpoint de borrado.

## Pendientes para Flutter

- [ ] Configurar la URL base por ambiente (local, pruebas y producción).
- [ ] Implementar cliente HTTP JSON con `Accept: application/json`, manejo de UTF-8 y bearer token Sanctum.
- [ ] Guardar el token de forma segura en el dispositivo y eliminarlo al cerrar sesión; no persistir credenciales de usuario.
- [ ] Implementar inicio/cierre de sesión, carga de `GET /me`, expiración/revocación y navegación ante 401.
- [ ] Mapear errores `403`, `404`, `409`, `422` y `429`, incluyendo `meta.errors` y `meta.retry_after`.
- [ ] Implementar paginación con los enlaces/metadatos que devuelve Laravel y preservar los filtros al cambiar de página.
- [ ] Implementar vistas y flujos de reservas, pedidos, catálogo, menú, pagos e incidencias respetando los permisos por rol.
- [ ] Enviar fechas con los formatos definidos por cada Form Request; mostrar fechas ISO-8601 devueltas por API según zona horaria.
- [ ] Reutilizar la misma `Idempotency-Key` al reintentar un registro de pago; generar una clave nueva al iniciar una operación distinta.
- [ ] No asumir que un pago `PENDIENTE` fue aprobado; esperar una futura integración de pagos antes de informar cobro exitoso.
- [ ] Probar la aplicación Flutter contra la colección Postman y los casos de error de este documento.
- [ ] Coordinar con backend cualquier cambio de contrato antes de su consumo en producción.
