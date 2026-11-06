# Preparación de sustentación — HostalConnect UNAB v1.0

## Alcance y reglas de la demostración

Este documento organiza una sustentación del sistema existente. No certifica una ejecución en vivo ni marca como realizada una prueba que solo tenga cobertura de código.

- Usar únicamente datos ficticios o cuentas dedicadas de prueba en un ambiente local/staging.
- No proyectar contraseñas, tokens, secretos, datos personales ni archivos `.env`.
- No probar con tarjetas, cobros reales o información de producción. El sistema no tiene una pasarela que apruebe pagos; los registros de pago quedan pendientes.
- Presentar los resultados con su alcance: pruebas Flutter remotas son del SHA indicado en el informe; la prueba HTTP con MySQL fue de un flujo acotado y no ejecutó la app Flutter real.
- No afirmar que producción, tiendas móviles, carga, alta disponibilidad o recuperación de backups están certificadas.

## Checklist técnico

- [ ] Confirmar rama, commit presentado y estado limpio del repositorio.
- [ ] Mostrar stack: Laravel 12/PHP 8.4, MySQL 8.4, Blade/Bootstrap, Sanctum y Flutter/Dart.
- [ ] Explicar requisitos del demo y ambiente usado; confirmar `/up` antes de iniciar.
- [ ] Mostrar de forma segura el estado de servicios/conexión sin imprimir variables sensibles.
- [ ] Presentar las pruebas ejecutadas y distinguirlas de la cobertura existente y de las pendientes.
- [ ] Verificar que la versión de aplicación/commit del material de presentación coincide con el código que se ejecuta.

## Checklist funcional

- [ ] Login válido y cierre de sesión.
- [ ] Dashboard según rol y sus estados vacíos/datos de prueba.
- [ ] Reservas: consulta de disponibilidad, creación y consulta; cancelar solo datos desechables.
- [ ] Pedidos: consultar y mostrar catálogo/detalle; crear o cambiar estado solo si el fixture es descartable.
- [ ] Productos y menú: lectura del catálogo/menú; mostrar gestión de borrador/publicación solo con usuario de personal y fixture preparado.
- [ ] Pagos: demostrar únicamente el registro pendiente/idempotencia; no presentar aprobación de pago.
- [ ] Incidencias: mostrar consulta/registro/actualización con usuario de personal si hay fixture.
- [ ] Salir al cerrar el demo y limpiar o cancelar registros desechables.

La secuencia puede ajustarse a las cuentas/fixtures disponibles. No se debe ejecutar una operación destructiva o irreversible en una base compartida.

## Checklist API

- [ ] Indicar base URL y prefijo `/api/v1` del ambiente de demostración.
- [ ] Ejecutar login con una cuenta de prueba y explicar que el token Sanctum es un secreto temporal.
- [ ] Mostrar `GET /api/v1/me` con Bearer y logout; no revelar el token en pantalla/captura.
- [ ] Demostrar una lectura paginada, por ejemplo `GET /api/v1/productos` o `GET /api/v1/reservas`.
- [ ] Si se realizan escrituras, usar IDs/datos desechables y explicar validación y autorización del servidor.
- [ ] Explicar ejemplos de error: 401 sin token, 403 rol/permiso insuficiente, 404 recurso ausente, 409 conflicto, 422 validación, 429 límite.
- [ ] Explicar `Idempotency-Key` en pagos y que no existe procesamiento real de tarjeta.
- [ ] Usar la colección Postman versionada; el entorno local predeterminado apunta a `http://localhost:8080`.

## Checklist Flutter

- [ ] Confirmar dispositivo/emulador, ambiente (`development`/`staging`) y URL base configurada.
- [ ] Explicar que Android Emulator usa `10.0.2.2`, mientras iOS Simulator usa `localhost`; dispositivo físico requiere host accesible por LAN.
- [ ] Mostrar inicio de sesión, restauración de sesión, manejo de 401 y logout.
- [ ] Mostrar lectura de catálogo y flujo de reserva/pedido que sea seguro para el fixture disponible.
- [ ] Explicar token almacenado con `flutter_secure_storage`; no mostrarlo ni pasarlo por argumentos.
- [ ] Si no se ejecutó el binario contra el servidor en el demo, decirlo expresamente: los tests unitarios/contrato usan transporte simulado.
- [ ] No llamar staging/producción desde la app sin autorización del responsable del ambiente.

## Checklist de seguridad

- [ ] Explicar que el backend aplica Sanctum, roles, Policies/Gate, ownership y Form Requests; las restricciones de la UI no son controles de seguridad.
- [ ] Mostrar únicamente casos preparados: 401 sin token y 403 por rol, evitando ráfagas o intentos con datos reales.
- [ ] Confirmar que no hay credenciales/tokens visibles en capturas, consola, Postman exportado ni presentación.
- [ ] Indicar el resultado correctamente: la revisión estática documentada no encontró vulnerabilidades demostrables en las superficies revisadas; no equivale a pentest ni certificación.
- [ ] Explicar controles pendientes de producción: TLS, secretos gestionados, debug desactivado, logging y backups probados.

## Checklist de arquitectura

- [ ] Explicar backend: Route → Form Request → Controller → autorización → Service → Repository → Model.
- [ ] Explicar que API Resources serializan respuestas; Blade presenta datos y no contiene reglas de negocio.
- [ ] Mostrar el diagrama del [README](../README.md) y el informe de [auditoría arquitectónica](./architecture-audit-v1.0.md).
- [ ] Explicar cliente: pantallas → controllers de estado → repositorios → `ApiClient`/API, con configuración y token centralizados.
- [ ] Aclarar que el informe auditó la implementación contra la arquitectura pedida para esta entrega, ya que no había un documento de diseño de referencia vigente en el repositorio.
- [ ] Explicar la optimización medida: disponibilidad de mesas pasó de 1+N consultas a una consulta en el caso probado de 20 mesas (~95% menos sentencias/round trips), no una medición de latencia.

## Checklist Git y release

- [x] Hay commits locales de las fases 1–8.
- [ ] Antes de sustentar, revisar `git status --short`, rama, `git log` y que el commit que se demuestra sea el acordado.
- [ ] Sincronizar la rama remota y resolver/documentar los nueve commits locales adelantados (incluido este checklist) antes de afirmar que están publicados.
- [ ] Verificar CI para el SHA final de la sustentación; no asumir que el resultado de otro SHA valida este HEAD.
- [ ] Confirmar si se requiere etiqueta/release y comprobarla solo después del acuerdo del equipo.
- [ ] No anunciar push, tag `v1.0` o release oficial hasta confirmar que se realizaron y verificaron.

Estado observado al iniciar la fase 9: rama `feature/v0.4-flutter-client`, árbol limpio y ocho commits locales por delante de `origin/feature/v0.4-flutter-client`; no se había hecho push. Este commit de fase agrega uno más: al cerrar la fase habrá nueve commits locales adelantados. Verificar de nuevo el estado al preparar la sustentación.

## Checklist de despliegue

- [ ] Usar un ambiente autorizado, separado de producción si es una demostración académica.
- [ ] Revisar el [checklist de despliegue](./deployment-checklist-v1.0.md); Compose actual es de desarrollo, no infraestructura de producción.
- [ ] Confirmar `APP_ENV=production` y `APP_DEBUG=false` solo para una instalación productiva y custodiar secretos fuera de Git.
- [ ] Verificar TLS, DNS, redes, DB privada y salud del servicio en la plataforma destino.
- [ ] Confirmar backup reciente y evidencia de restauración antes de operaciones sobre datos reales.
- [ ] No reportar el scheduler/workers como componentes desplegados: la búsqueda de código no encontró tareas programadas ni jobs asíncronos activos.
- [ ] Si se demuestra en local, explicitarlo como demo local y no como despliegue productivo.

## Flujo sugerido de demostración (15–20 minutos)

1. **Contexto (1–2 min):** problema atendido, roles y objetivo del sistema; limitar alcance a módulos implementados.
2. **Arquitectura (2–3 min):** explicar el diagrama y las capas; mostrar estructura web/API/Flutter en el repositorio.
3. **Web (4–5 min):** login de prueba, dashboard por rol, catálogo/menú y un flujo de reserva o pedido.
4. **API (3–4 min):** ejecutar login, `/me`, una lectura y, si está preparada, una escritura segura en Postman; explicar Bearer, paginación y un caso 401/403/422.
5. **Flutter (3–4 min):** mostrar login, catálogo y una lectura/escritura preparada; aclarar explícitamente si el backend está simulado o si se trata de una conexión live.
6. **Calidad/seguridad (2 min):** enseñar cobertura, resultados de ejecución, ownership/permisos, idempotencia y límites de la auditoría.
7. **Cierre (1 min):** pendientes de integración Flutter live, determinismo de suite completa, publicación Git y preparación real de producción.

Si una pantalla, cuenta, conexión o servicio no está disponible, omitir esa demostración y presentar la evidencia documentada; no improvisar con datos productivos.

## Casos de prueba para preparar

| Caso | Preparación | Acción | Resultado esperado | Estado de evidencia |
|---|---|---|---|---|
| Autenticación válida | Usuario de prueba activo | Login web o `POST /api/v1/login` | Sesión/token válida y perfil permitido | Cubierto por tests; demo requiere fixture local |
| Credenciales inválidas | Cuenta ficticia/no válida | Login API | HTTP 401; no crea sesión/token válido | Suite API y flujo HTTP de integración cubren resultado |
| Ruta protegida sin token | No enviar Bearer | `GET /api/v1/me` | HTTP 401 estandarizado | Cubierto por suite API e integración HTTP |
| Autorización por rol | Token CLIENTE dedicado | Consultar incidencias de personal | HTTP 403, sin filtrar datos restringidos | Suite API e integración HTTP |
| Validación de entrada | Payload vacío/no válido en fixture aislado | Crear reserva | HTTP 422 con errores de campo; sin escritura inválida | Suite API e integración HTTP (payload vacío) |
| Reserva y consistencia | Mesa/horario disponibles en base desechable | Consultar disponibilidad, crear y releer reserva | Creación aceptada y lectura refleja el registro | Flujo HTTP API→Laravel→MySQL probado |
| Conflicto de disponibilidad | Dos sesiones y fixture de mesa/horario | Intentar dos reservas superpuestas | Segunda operación rechazada con 409 | Contrato/código cubierto; no reportado como prueba de carga/concurrencia |
| Token revocado | Token de cuenta de prueba | Logout y luego llamar `/me` con token previo | Logout 200 y `/me` 401 | Suite API e integración HTTP |
| Pago idempotente | Pedido de prueba sin cobro real | Repetir con igual `Idempotency-Key` y luego variar pedido/canal | Reintento equivalente no duplica; reutilización incompatible es conflicto | Cubierto por tests; demo sin pasarela real |
| Flutter con API live | Staging aprobado y cuenta cliente dedicada | Ejecutar `live_api_smoke_test.dart` | Flujos definidos por smoke test contra servidor | Pendiente; no afirmarlo como completado |

No ejecutar el caso de concurrencia, idempotencia de pagos o escrituras de demostración en producción.

## Preguntas probables y respuestas sugeridas

1. **¿Por qué separaron Controller, Request, Service, Repository y Model?**  
   Los Requests concentran validación, Controllers adaptan HTTP, Services coordinan casos de uso, Repositories encapsulan consultas/persistencia y Models representan entidades/relaciones. Mantiene responsabilidades separadas sin rediseñar el sistema.

2. **¿Cómo se protegen los endpoints y los datos de cada cliente?**  
   Sanctum autentica mediante token; middleware de rol, Policies y Gate autorizan acciones, y las consultas/accesos aplican ownership. Las decisiones efectivas permanecen en Laravel, no en Flutter.

3. **¿Qué ocurre si vence o se revoca el token?**  
   La API responde 401; Flutter elimina la sesión local y vuelve al login. Logout revoca el token actual en servidor.

4. **¿Cómo evitan reservas simultáneas incompatibles?**  
   Disponibilidad filtra en base de datos mesas no ocupadas por reservas solapadas no canceladas y la creación vuelve a validar. Un conflicto se devuelve como 409. La prueba de disponibilidad mide consultas, no una prueba de concurrencia bajo carga.

5. **¿Por qué los pagos no muestran éxito de cobro?**  
   No hay pasarela integrada: el backend registra una solicitud pendiente, valida idempotencia y no recibe datos de tarjeta ni confirma dinero.

6. **¿Qué optimización se logró y cómo se midió?**  
   El endpoint de disponibilidad sustituyó una consulta más N comprobaciones por una consulta relacional. Para 20 mesas candidatas, 21 sentencias pasan a 1 (~95% menos sentencias); no se midió latencia ni CPU/RAM.

7. **¿Qué pruebas se ejecutaron exactamente?**  
   La suite Laravel ejecutada reportó 136 pruebas/863 assertions y usa SQLite en memoria; además, un flujo HTTP acotado usó MySQL. Flutter CI reportó 72 pruebas y 1 omitida en un SHA remoto. El binario Flutter contra API/MySQL live no se ejecutó. No deben sumarse como una única ejecución integrada.

8. **¿El proyecto está listo para producción?**  
   La revisión de preparación no certifica producción. Compose es local y falta definir/desplegar TLS, secretos, artefactos de release, monitoreo y backups con restauración probada.

9. **¿Qué deuda queda?**  
   Entre los pendientes conocidos están ejecutar Flutter real contra staging, estabilizar la suite completa cuando aparecen fallos intermitentes en algunos filtros/PATCH, validar CI para el commit final y preparar infraestructura productiva.

10. **¿La arquitectura se contrastó con un diagrama inicial aprobado?**  
    No se encontró un documento de diseño vigente en el repositorio. La auditoría compara el código con la arquitectura exigida para esta entrega y describe esa limitación.

11. **¿Ya se publicó la versión v1.0?**  
    No se debe decir que sí sin verificarlo. Al cerrar la fase 9 habrá nueve commits locales por delante de la rama remota, sin push y sin tag de release confirmado.

## Evidencia y límites de fechas/CI

- [Auditoría arquitectónica](./architecture-audit-v1.0.md)
- [Auditoría de seguridad](./security-audit-v1.0.md)
- [Checklist funcional y evidencia CI](./functional-test-checklist-v1.0.md)
- [Informe de integración](./integration-test-report-v1.0.md)
- [Revisión de rendimiento](./performance-review-v1.0.md)
- [Contrato API/Postman](./api-v0.3-validation.md)
- [Checklist de despliegue](./deployment-checklist-v1.0.md)

La evidencia funcional documenta Mobile CI para el SHA `ef93d12182706744e20602e40e9b11e008422725`, no para el HEAD local actual. Además, ese informe muestra una fecha `2026-11-06`, posterior a la fecha de preparación de este checklist (`2026-11-05`); verificar fecha/registro de CI antes de presentar la ejecución como evidencia temporal confirmada.
