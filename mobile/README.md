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

GitHub Actions ejecuta `flutter analyze` y `flutter test` al abrir/actualizar un pull request y en cada push que cambie `mobile/` o el propio workflow. La verificación usa Flutter 3.44.9 y no requiere secretos; el smoke test contra staging se omite si no existe su configuración local.

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

## Preparación de distribución Android e iOS

Se incluyen proyectos nativos Flutter para Android e iOS. Ambos usan el identificador `co.edu.unab.hostalconnect` y muestran `HostalConnect UNAB`; se asumieron a partir del nombre y dominio institucional del proyecto. **Confirma el identificador con UNAB antes de la primera publicación:** Play Store y App Store lo tratan como identidad estable y no se debe cambiar después de distribuir. El ícono actual es el generado por Flutter y es solo temporal; sustitúyelo por los recursos oficiales aprobados para Android e iOS antes de publicar.

La versión y el número de build salen de `version` en `pubspec.yaml`; incrementa el sufijo numérico (`+N`) en cada entrega a tiendas. Para Android, configura la firma de release con un keystore de upload fuera del repositorio. Genera uno en un lugar privado:

```bash
keytool -genkey -v -keystore /ruta-segura/hostalconnect-upload.jks -keyalg RSA -keysize 2048 -validity 10000 -alias hostalconnect
```

En un equipo de desarrollo crea `android/key.properties` (ignorado por Git):

```properties
storeFile=/ruta-segura/hostalconnect-upload.jks
storePassword=REEMPLAZAR_LOCALMENTE
keyAlias=hostalconnect
keyPassword=REEMPLAZAR_LOCALMENTE
```

En CI, configura en su lugar `ANDROID_KEYSTORE_PATH`, `ANDROID_STORE_PASSWORD`, `ANDROID_KEY_ALIAS` y `ANDROID_KEY_PASSWORD` como variables/secretos protegidos. `storeFile` puede ser una ruta absoluta. El build de release falla si la firma no está configurada y nunca usa la clave debug. Respalda el keystore de forma segura; no lo subas a Git ni lo pierdas.

```bash
flutter build appbundle --release \
  --dart-define=APP_ENV=production \
  --dart-define=API_BASE_URL=https://api.tu-dominio-real.edu.co
```

Para iOS se requiere macOS con Xcode, una cuenta Apple Developer y provisioning/signing team válido. Abre `ios/Runner.xcworkspace` en Xcode, selecciona el equipo de firma para Runner y RunnerTests y valida el bundle ID antes de archivar; después genera el archive o ejecuta:

```bash
flutter build ipa --release \
  --dart-define=APP_ENV=production \
  --dart-define=API_BASE_URL=https://api.tu-dominio-real.edu.co
```

El permiso de red es explícito. Android permite HTTP claro solo en variantes debug/profile para servidores locales; release exige TLS. iOS mantiene ATS y permite networking local para desarrollo, sin habilitar HTTP arbitrario. Estos archivos dejan preparada la configuración, pero los artefactos firmados y la publicación no se pueden validar desde Linux sin Android SDK/keystore o macOS/Xcode/cuenta Apple.

## Instalar en un Android físico

En el computador instala Flutter, Android SDK Platform Tools (`adb`) y las herramientas Android requeridas por Flutter. En el teléfono activa **Opciones de desarrollador → Depuración USB**, conéctalo por USB y acepta el diálogo de autorización. Desde `mobile/`, verifica que Flutter detecte el teléfono:

```bash
flutter doctor
flutter devices
adb devices
```

Para probar el backend local sin exponerlo a la red, crea un túnel ADB y ejecuta la variante debug:

```bash
adb reverse tcp:8080 tcp:8080
flutter run \
  --dart-define=APP_ENV=development \
  --dart-define=API_BASE_URL=http://127.0.0.1:8080
```

Para generar un APK instalable de pruebas que consuma Render, reemplaza el dominio por el HTTPS real:

```bash
flutter build apk --debug \
  --dart-define=APP_ENV=development \
  --dart-define=API_BASE_URL=https://hostalconnect-unab.onrender.com
adb install -r build/app/outputs/flutter-apk/app-debug.apk
```

El APK queda en `build/app/outputs/flutter-apk/app-debug.apk`. Es una compilación debug para pruebas, no un artefacto de publicación. Para una versión release instala primero la firma de Android indicada arriba, usa `APP_ENV=production` y una URL HTTPS real, y genera el APK con `flutter build apk --release`. Confirma que el backend Render esté desplegado y `/up` responda antes de probar el cliente.

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

## Validación contra staging y dispositivos

La suite unitaria no necesita red. Para ejecutar además `test/live_api_smoke_test.dart` contra un staging real, crea localmente `mobile/.live-api-test.json` (está ignorado por Git) con una cuenta de cliente dedicada y descartable:

```json
{
  "APP_ENV": "staging",
  "LIVE_API_BASE_URL": "https://staging-api.tu-dominio-real.edu.co",
  "LIVE_API_EMAIL": "cliente-smoke@tu-dominio-real.edu.co",
  "LIVE_API_PASSWORD": "contraseña-descartable"
}
```

Desde `mobile/`, ejecuta:

```bash
flutter test --no-pub --dart-define-from-file=.live-api-test.json test/live_api_smoke_test.dart
```

La prueba verifica conectividad y contratos reales de autenticación, `/me`, catálogo, menú paginado, pedidos, reservas y disponibilidad; también espera 401 sin token, 403 para acceso de cliente a incidencias de personal, 404 por ID inválido y 422 con errores de validación. Solo crea un token Sanctum temporal mediante login y lo revoca al cerrar sesión; no crea pedidos, reservas, incidencias ni pagos. Si faltan los valores LIVE, el caso aparece como omitido. Nunca uses cuentas ni datos de producción.

Esta prueba corre en el host que ejecuta Flutter, no en el dispositivo. Para validar el transporte desde un dispositivo físico, inicia Laravel en una dirección accesible por la red local y usa esa URL LAN en desarrollo; para staging, inicia la app con la URL HTTPS real mediante `flutter run --dart-define=APP_ENV=staging --dart-define=API_BASE_URL=https://staging-api.tu-dominio-real.edu.co`. En la app, inicia sesión manualmente con una cuenta de pruebas. Comprueba login/logout, restauración de sesión, paginación, y que reservas/pedidos actualizan la lista al volver; en Android/iOS confirma permisos de red, confianza TLS y que el certificado HTTPS sea válido. No copies credenciales a los argumentos de compilación.

### Casos manuales que requieren fixtures en staging

- **409 reserva:** con dos clientes/equipos, consulta una mesa disponible y crea simultáneamente reservas superpuestas; la segunda solicitud debe mostrar el conflicto y refrescar disponibilidad. Usa una ventana y datos desechables, y cancela la reserva creada.
- **429 límite de peticiones:** reproduce el caso solo en staging coordinado con el responsable del backend; no generes ráfagas contra producción. Confirma el mensaje y `Retry-After`/`meta.retry_after` sin reintento automático agresivo.
- **Idempotencia de pago:** con un pedido de pruebas y pago pendiente, reintenta la misma operación con la misma clave y verifica que no se duplique; una operación nueva debe usar otra clave. No solicites aprobación real ni pruebes con medios de pago productivos.
- **Fechas/zona horaria:** crea una reserva de prueba cerca de medianoche en la zona horaria acordada, verifica los valores enviados `Y-m-d\\TH:i` y compara la hora mostrada con el ISO-8601 de la API.

Registra fecha, versión de app, modelo/OS del dispositivo, ambiente, resultado esperado/obtenido y un identificador de correlación no sensible. No guardes tokens, contraseñas ni datos personales en capturas o reportes. La prueba live no se ejecutó automáticamente durante el desarrollo porque este entorno no tiene credenciales ni un endpoint de staging configurado.
