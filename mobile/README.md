# HostalConnect UNAB Mobile

Aplicación Flutter para consumir la API Laravel de HostalConnect UNAB.

## Requisitos

- Flutter Stable 3.44.0 o posterior.
- Dart 3.12.0 o posterior.

## Preparación

Desde este directorio:

```bash
flutter pub get
flutter analyze
flutter test
```

La app registra `http.Client`, `FlutterSecureStorage`, `ApiConfig` y `ApiClient` con Provider y configura GoRouter. La infraestructura API vive en `lib/core/network/` y usa el prefijo `/api/v1`.

El ambiente y la URL se configuran al compilar con `APP_ENV` (`development`, `staging` o `production`) y `API_BASE_URL`. Desarrollo usa por defecto `http://10.0.2.2:8080` para el emulador Android; para iOS Simulator define `http://localhost:8080`, y para un dispositivo físico define la dirección LAN del host. Staging y producción requieren una URL explícita con HTTPS y rechazan hosts locales. Producción también rechaza direcciones IP y dominios reservados/de ejemplo; la app valida el formato y la clase de host, pero la existencia y titularidad del dominio deben verificarse al desplegar.

Ejemplos de ejecución desde `mobile/`:

```bash
# Android Emulator conectado a Laravel en el host
flutter run --dart-define=APP_ENV=development --dart-define=API_BASE_URL=http://10.0.2.2:8080

# Sustituye el dominio de ejemplo por el host HTTPS real de staging
flutter run --dart-define=APP_ENV=staging --dart-define=API_BASE_URL=https://staging-api.example.org

# Sustituye por el dominio público real de producción
flutter build appbundle --release --dart-define=APP_ENV=production --dart-define=API_BASE_URL=https://api.tu-dominio-real.edu.co
```

`API_BASE_URL` no es un secreto y debe apuntar al origen, sin ruta `/api/v1`, credenciales, query ni fragmento. No pases claves, contraseñas ni tokens mediante `--dart-define`.

El cliente agrega `Accept: application/json`, serializa cuerpos como JSON y lee el token opcional de `flutter_secure_storage` mediante un callback asíncrono. El token usa la clave `sanctum_access_token`. Las respuestas de error se transforman en `ApiException` sin exponer detalles del servidor en errores 5xx.

## Autenticación

El inicio de sesión usa `POST /api/v1/login` con `correo` y `password`. El token Sanctum se guarda únicamente en el almacenamiento seguro; las credenciales no se persisten. Al abrir la app se consulta `GET /api/v1/me` para restaurar la sesión. El cierre usa `POST /api/v1/logout` y elimina el token local incluso si la API no está disponible. Las respuestas 401 invalidan la sesión y redirigen al login.

La ruta `/` requiere una sesión autenticada y muestra el catálogo permitido para el rol autenticado y el menú publicado para hoy. Los productos inactivos que pueda consultar un administrador se identifican como no disponibles. El catálogo acepta búsqueda y usa la paginación devuelta por Laravel. La falta de un menú publicado se presenta como estado vacío; otros errores conservan su mensaje y permiten reintentar.

Los clientes pueden agregar productos disponibles al carrito, crear pedidos inmediatos o programados sin mesa, consultar sus pedidos paginados y solicitar la cancelación de pedidos registrados. El servidor calcula el total y autoriza cada cancelación. Desde el detalle del pedido pueden consultar pagos y solicitar el registro de un pago en línea o presencial. Estas solicitudes requieren `Idempotency-Key`; el cliente reutiliza la clave al reintentar una operación fallida, muestra el estado devuelto por el servidor y no procesa tarjetas ni confirma pagos pendientes. La disponibilidad de mesas no se simula en el cliente.

El personal (`ADMINISTRADOR` y `OPERADOR`) puede consultar y filtrar los pedidos de operación, ver el nombre del cliente y avanzar el estado por las transiciones permitidas. La API sigue siendo la autoridad para validar rol y transición; las acciones de pago y cancelación del cliente no se muestran al personal.

Los clientes también pueden consultar mesas disponibles por fecha, intervalo y cantidad de personas, crear una reserva seleccionando una mesa devuelta por la API, listar sus reservas con filtros y paginación, y cancelar una reserva. La disponibilidad se valida nuevamente en el servidor al crearla; si otra reserva ocupa la mesa entre la consulta y la confirmación, el cliente muestra el conflicto y actualiza la lista de mesas.

El personal (`ADMINISTRADOR` y `OPERADOR`) puede reportar incidencias, consultar la lista paginada con búsqueda y filtros de estado/prioridad, y actualizar su estado o prioridad. La política de la API autoriza estas operaciones; el cliente no envía el usuario reportante ni define el estado inicial.

El personal también puede crear y editar borradores del menú del día, seleccionar productos disponibles y publicarlos. La app conserva en la edición los productos inactivos ya incluidos en un borrador, y el backend sigue validando fechas únicas, selecciones y publicación.

El repositorio API y Flutter comparten el mismo proyecto Git, pero el código móvil vive aislado en `mobile/`.
