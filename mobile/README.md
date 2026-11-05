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

La URL base se configura al compilar con `--dart-define=API_BASE_URL=...`. El valor local por defecto `http://10.0.2.2:8080` es para el emulador Android accediendo a Laravel en el host. Para iOS Simulator usa `http://localhost:8080`; en un dispositivo físico usa la dirección LAN del host. En entornos desplegados usa HTTPS.

El cliente agrega `Accept: application/json`, serializa cuerpos como JSON y lee el token opcional de `flutter_secure_storage` mediante un callback asíncrono. El token usa la clave `sanctum_access_token`. Las respuestas de error se transforman en `ApiException` sin exponer detalles del servidor en errores 5xx.

La ruta raíz no dibuja interfaz; no hay pantallas ni flujos de autenticación en esta fase.

El repositorio API y Flutter comparten el mismo proyecto Git, pero el código móvil vive aislado en `mobile/`.
