# Auditoría de seguridad — HostalConnect UNAB v1.0

**Fase:** 3 — Seguridad
**Fecha:** 2026-11-05
**Tipo:** revisión estática de código fuente; sin pruebas dinámicas.

## Alcance revisado

- Autenticación web y API.
- Sanctum, emisión y revocación de tokens.
- Middleware, roles, Policies, Gate y ownership.
- Protección CSRF de formularios web.
- Validación y autorización en Form Requests.
- Controles de acceso de reservas, pedidos, pagos, incidencias, menús y productos.
- Transporte del token Sanctum desde Flutter.

## Resultado

La revisión de seguridad del backend no encontró fallos demostrables en las superficies revisadas. Las rutas web y API aplican autenticación/middleware; los endpoints API protegidos usan `auth:sanctum`, y el acceso a recursos se controla mediante Policies/Gate y ownership. Las entradas usan Form Requests y las rutas web mantienen la protección CSRF estándar de Laravel.

Flutter obtiene el token del almacenamiento configurado y `ApiClient` lo envía con el esquema Bearer. La prueba existente de `ApiClient` comprueba la cabecera con un token de muestra. La aparente asignación de asteriscos mencionada en el informe arquitectónico fue un efecto de redacción de la salida de herramientas, no un defecto del código; se corrigió esa conclusión en [architecture-audit-v1.0.md](./architecture-audit-v1.0.md).

| # | Severidad | Archivo | Líneas | Vulnerabilidad | Confianza |
|---|---|---|---|---|---|
| — | — | — | — | No se identificaron vulnerabilidades demostrables en el alcance revisado. | — |

## Cambios realizados

- No se modificó lógica de autenticación ni autorización: la revisión no encontró correcciones de código justificadas.
- Se reemplazó en la prueba de configuración un URL de ejemplo con credenciales por `https://user@example.test/api`; sigue verificando rechazo de URL con userinfo/path sin almacenar contraseña en el fixture.
- Se corrigió el hallazgo falso sobre la cabecera Bearer en el informe de arquitectura.

## Validación y límites

- `git diff --check`: aprobado.
- La prueba Flutter existente y las suites PHP no pudieron ejecutarse: en este entorno no están disponibles Flutter/Dart, PHP/Composer ni `vendor/bin/pint`.
- No se ejecutaron pruebas dinámicas ni contra servicios desplegados. El resultado representa revisión estática del código actual y no una certificación de ausencia absoluta de vulnerabilidades.
