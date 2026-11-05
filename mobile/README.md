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

La app registra las dependencias base (`http.Client` y `FlutterSecureStorage`) con Provider y configura GoRouter. La ruta raíz no dibuja interfaz; no hay pantallas ni módulos funcionales en esta fase.

El repositorio API y Flutter comparten el mismo proyecto Git, pero el código móvil vive aislado en `mobile/`.
