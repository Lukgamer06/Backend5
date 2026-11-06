# HostalConnect UNAB

Sistema de gestión de hostal con aplicación web Laravel, API REST versionada y cliente Flutter. Esta entrega consolida los módulos existentes para validación y demostración; no incorpora funcionalidades nuevas.

## Tecnologías

| Componente | Tecnología |
| --- | --- |
| Backend y web | PHP 8.4, Laravel 12, Blade |
| Base de datos | MySQL 8.4 |
| API | REST `/api/v1`, Laravel Sanctum, API Resources |
| Interfaz web | Blade, Bootstrap 5 |
| Cliente móvil | Flutter, Dart, Provider, GoRouter |
| Entorno local | Docker Compose, PHP-FPM, Nginx |

## Requisitos

- Docker Engine y Docker Compose v2 para ejecutar el backend.
- Flutter Stable 3.44.0+ y Dart 3.12.0+ para desarrollar/ejecutar `mobile/`.
- Git para clonar el repositorio.

## Inicio local con Docker

Desde la raíz del repositorio:

```bash
cp .env.example .env
```

Configura en `.env` contraseñas locales para `DB_PASSWORD` y `DB_ROOT_PASSWORD`. Los valores de ejemplo son solo para desarrollo. Luego inicia los servicios, genera la clave de Laravel y aplica las migraciones:

```bash
docker compose up --build -d
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

La aplicación web/API queda disponible en `http://localhost:8080` (el puerto se puede cambiar con `APP_HTTP_PORT`). `/up` es el endpoint de salud de Laravel. MySQL solo está expuesto dentro de la red de Compose y sus datos persisten en el volumen `mysql_data`.

Para poblar una base local vacía con datos de demostración generados por factories:

```bash
docker compose exec app php artisan db:seed
```

El seeder genera datos ficticios; no lo ejecutes sobre una base compartida o de producción. Las cuentas válidas para probar deben configurarse localmente y no se distribuyen credenciales compartidas en este README. Al detener la aplicación, `docker compose down` conserva los datos; `docker compose down --volumes` también elimina los volúmenes y sus datos.

## Arquitectura

```mermaid
flowchart LR
    Browser[Web: Blade / Bootstrap] --> Web[Laravel Web]
    Flutter[Cliente Flutter] -->|HTTPS / JSON / Bearer Sanctum| API[API REST /api/v1]
    Web --> Controllers[Controllers]
    API --> Controllers
    Controllers --> Requests[Form Requests]
    Requests --> Services[Services]
    Services --> Repositories[Repositories]
    Repositories --> Models[Models / Eloquent]
    Models --> MySQL[(MySQL)]
    Controllers --> Resources[API Resources]
    Resources --> Flutter
```

En las operaciones de aplicación se mantiene la separación Controller → Request → Service → Repository → Model; las respuestas API se serializan con Resources. Las vistas Blade presentan la información y no deben contener lógica de negocio. La autorización se realiza mediante middleware de roles y Policies/Gates; el backend conserva la autoridad sobre ownership, validaciones y transiciones.

## Módulos

- Autenticación y dashboard con acceso por rol (`ADMINISTRADOR`, `OPERADOR`, `CLIENTE`).
- Reservas y consulta de disponibilidad de mesas.
- Pedidos, detalle y transiciones de estado.
- Catálogo de productos y menú del día.
- Registro/consulta de pagos e incidencias.
- Cliente móvil: autenticación, catálogo, pedidos, pagos, reservas, incidencias y gestión de menú para personal.

## API REST

La API está bajo `/api/v1`. `POST /api/v1/login` permite iniciar sesión; las demás rutas requieren `Authorization: Bearer <token>` emitido por Sanctum. `POST /api/v1/logout` revoca el token actual y `GET /api/v1/me` devuelve la identidad autenticada. Las respuestas JSON usan `data`, `message` y `meta`; las colecciones añaden `links`.

| Recurso | Operaciones principales |
| --- | --- |
| Incidencias | listar, reportar, actualizar |
| Reservas | listar, disponibilidad, crear, consultar, actualizar, cancelar |
| Pedidos y pagos | listar/crear/consultar/actualizar/cancelar pedidos; cambiar estado; listar/crear/consultar pagos |
| Productos | listar y consultar |
| Menús del día | listar, consultar menú actual, crear/actualizar borrador, publicar |

La colección Postman [HostalConnect-UNAB-API-v1.postman_collection.json](./docs/postman/HostalConnect-UNAB-API-v1.postman_collection.json) contiene 29 solicitudes para las rutas actuales. Importa también el entorno [HostalConnect-UNAB-Local.postman_environment.json](./docs/postman/HostalConnect-UNAB-Local.postman_environment.json), usa `http://localhost:8080` como `baseUrl`, completa localmente las credenciales de una cuenta válida y ejecuta primero **Iniciar sesión**. Los IDs de ejemplo dependen de los datos presentes en la base. No guardes tokens ni contraseñas en archivos versionados.

Las operaciones de registro de pago requieren `Idempotency-Key`; el backend registra la solicitud como pendiente y no procesa tarjetas ni confirma un cobro real. Los errores incluyen códigos como 401, 403, 404, 405, 409, 422 y 429.

## Cliente Flutter

El proyecto móvil vive en [`mobile/`](./mobile/). Sigue su [guía de instalación, configuración por ambiente y pruebas](./mobile/README.md). En resumen:

```bash
cd mobile
flutter pub get
flutter analyze
flutter test
```

Para Android Emulator contra el backend local:

```bash
flutter run --dart-define=APP_ENV=development --dart-define=API_BASE_URL=http://10.0.2.2:8080
```

Para iOS Simulator usa `http://localhost:8080`; para un dispositivo físico, la dirección LAN del host. Staging y producción requieren una URL HTTPS explícita. No pases secretos mediante `--dart-define`.

## Pruebas y estado de validación

Backend, desde el servicio `app`:

```bash
docker compose exec app php artisan test
docker compose exec app vendor/bin/pint --test
```

Para validar la configuración y las rutas:

```bash
docker compose exec app php artisan route:list
docker compose exec app php artisan route:list --path=api/v1
git diff --check
```

La suite Laravel usa SQLite en memoria. El informe de integración registra una ejecución HTTP contra Laravel/MySQL y aclara que no se ejecutó el binario Flutter contra una API/MySQL viva. Las pruebas Flutter registradas por CI fueron 72 aprobadas y 1 omitida por requerir configuración de staging; consulta los informes para el alcance exacto y los límites de esa evidencia:

- [Auditoría arquitectónica](./docs/architecture-audit-v1.0.md)
- [Auditoría de seguridad](./docs/security-audit-v1.0.md)
- [Checklist de pruebas funcionales](./docs/functional-test-checklist-v1.0.md)
- [Informe de integración](./docs/integration-test-report-v1.0.md)
- [Revisión de rendimiento](./docs/performance-review-v1.0.md)
- [Contrato y validación de API](./docs/api-v0.3-validation.md)

No se ha certificado despliegue productivo, carga/estrés, firma/publicación en tiendas ni integración Flutter en vivo. Antes de desplegar, configura secretos fuera de Git, `APP_ENV=production`, `APP_DEBUG=false`, TLS, almacenamiento y backups, logs, scheduler/colas si aplican y ejecuta migraciones revisadas. Esta configuración Compose es para desarrollo local, no una arquitectura de producción.

Consulta el [checklist de despliegue v1.0](./docs/deployment-checklist-v1.0.md) para hallazgos, criterios de salida y verificaciones pendientes antes de producción.
