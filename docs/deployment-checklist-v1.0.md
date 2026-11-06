# Checklist de despliegue v1.0

## Alcance y decisión

La configuración actual de Docker Compose sirve para desarrollo local y demostración, no es una configuración de producción certificada. No se realizó ningún despliegue ni se modificaron secretos o configuración local.

**Estado:** no listo para producción hasta resolver los bloqueadores de seguridad y operación señalados en esta lista. Esta revisión es estática; no reemplaza una validación de infraestructura, proveedor, DNS, TLS o restauración en el entorno destino.

## Hallazgos de configuración

| Área | Evidencia actual | Estado de producción |
| --- | --- | --- |
| Entorno y debug | `.env.example` usa `APP_ENV=local`, `APP_DEBUG=true` y `LOG_LEVEL=debug`. El default de `config/app.php` es `APP_DEBUG=false`, pero en Laravel prevalece el valor del entorno. | Bloquea producción: definir `APP_ENV=production`, `APP_DEBUG=false` y revisar configuración cacheada. |
| Secretos | `.env.example` trae credenciales fijas para uso local. `.env` está excluido de Git, pero no se debe reutilizar el ejemplo como secreto de producción. | Generar contraseñas fuertes e individuales y `APP_KEY` en el destino; usar un almacén de secretos, restringir acceso y no subirlos al repositorio o a imágenes. |
| Exposición de red | Nginx publica HTTP en `127.0.0.1:${APP_HTTP_PORT:-8080}`. No hay TLS ni terminación HTTPS en el Compose actual; la base de datos no publica puerto al host. | Configurar un reverse proxy/load balancer con TLS, redirección HTTP→HTTPS, certificados renovables y cabeceras proxy confiables antes de admitir tráfico externo. Conservar la base de datos en una red privada. |
| Artefacto | `Dockerfile` usa una sola etapa, conserva Composer y Git en la imagen final y ejecuta `composer install` sin `--no-dev`. El Compose monta el código local en los contenedores de aplicación y Nginx. | No usar esta topología como release inmutable. Construir y desplegar una imagen versionada e inmutable; evaluar una compilación multi-stage que no incluya herramientas ni dependencias de desarrollo en runtime y validar permisos/usuario de ejecución. |
| Persistencia | MySQL y el almacenamiento Laravel usan volúmenes nombrados, lo cual persiste frente a la recreación de contenedores. No hay política o destino de backups definido en Compose. | Persistencia no es backup: definir respaldos automatizados, cifrados, fuera del host y con retención; probar restauración y RPO/RTO. Incluir archivos persistentes si se incorporan o almacenan en `storage/`. |
| Logs | El ejemplo usa canal `single` y nivel `debug`. Laravel también admite salida `stderr` y canal `daily`. | Definir nivel apropiado, centralizar y retener logs, monitorear errores y evitar datos sensibles/tokens en logs. |
| Scheduler y colas | La búsqueda de código no encontró tareas `Schedule::`, clases `ShouldQueue` ni despachos de jobs. El ejemplo usa `QUEUE_CONNECTION=sync`; Compose no define worker ni scheduler. | No hacen falta worker/scheduler para el comportamiento encontrado. Antes de introducir jobs o tareas programadas, diseñar su driver, almacenamiento/migraciones, supervisión y ejecución persistente. |
| Salud/operación | Hay healthcheck de MySQL y socket PHP-FPM; Nginx no tiene healthcheck propio ni se define observabilidad o alertas. | Comprobar salud HTTP a través del proxy, métricas/alertas, reinicios, capacidad, disco y restauración en el destino real. |
| Sesiones/almacenamiento | El ejemplo usa sesiones y cache en archivo y filesystem local. | Adecuado para instancia local única. Para varias réplicas, usar almacenamiento compartido compatible, revisar afinidad de sesión/cache y definir persistencia/backup de los archivos requeridos. |

## Checklist antes de producción

### Configuración y secretos

- [ ] Crear configuración propia del entorno destino sin copiar credenciales del `.env.example`.
- [ ] Establecer `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://<dominio-real>` y `LOG_LEVEL` apropiado.
- [ ] Generar y custodiar una `APP_KEY` estable; respaldarla de forma segura y no regenerarla en cada despliegue.
- [ ] Crear credenciales de DB fuertes; la aplicación debe usar un usuario limitado a su base, sin usar el usuario root.
- [ ] Guardar secretos en el gestor de secretos del proveedor/entorno, limitando quién puede leerlos. No incluirlos en Git, logs, imágenes o argumentos de build.
- [ ] Revisar variables de correo, almacenamiento, sesión, cache y demás integraciones habilitadas; el ejemplo actual usa correo `log`.
- [ ] Verificar que la configuración cacheada corresponda al entorno y a sus variables, sin exponer configuración o excepciones detalladas al cliente.

### Imagen, red y TLS

- [ ] Construir una imagen desde un commit etiquetado, escanearla y promover el mismo artefacto entre ambientes.
- [ ] Reemplazar el bind mount del código fuente por el artefacto inmutable; instalar dependencias de producción (`--no-dev`) sin herramientas de build innecesarias en runtime.
- [ ] Servir tráfico externo solo detrás de TLS vigente; validar renovación, redirección HTTPS y configuración de proxy.
- [ ] Mantener MySQL en red privada, abrir solo los puertos necesarios y aplicar controles de acceso del proveedor.
- [ ] Confirmar que Nginx solo sirve `public/`, que `.env` y archivos internos no son descargables y que los límites de petición/cabeceras son apropiados.
- [ ] Ejecutar con privilegios mínimos, revisar permisos de `storage` y `bootstrap/cache`, y definir límites/capacidad de CPU, RAM y disco.

### Base de datos y recuperación

- [ ] Revisar migraciones y realizar backup verificado antes de migrar o actualizar.
- [ ] Aplicar migraciones de forma controlada durante el release, con aprobación y estrategia de reversión compatible.
- [ ] Configurar backup automatizado de MySQL cifrado, externo al host, con política de retención y alertas ante fallas.
- [ ] Probar periódicamente la restauración en un ambiente aislado y registrar RPO/RTO alcanzados.
- [ ] Determinar si hay archivos de aplicación que deban respaldarse junto con la base; probar recuperación consistente.

### Logs, salud y tareas en segundo plano

- [ ] Centralizar logs y configurar retención, alertas y protección de datos personales/secrets.
- [ ] Monitorear endpoint HTTP `/up`, errores 5xx, disponibilidad de DB, uso de recursos, espacio en disco y certificados TLS.
- [ ] Verificar reinicio y recuperación de cada servicio tras reinicio de host/instancia.
- [ ] Confirmar que el flujo actual no requiere scheduler ni worker; no desplegarlos como requisito ficticio.
- [ ] Si se agregan jobs/tareas luego, revisar migraciones de colas, política de reintentos, fallidos, idempotencia, worker supervisado y scheduler de una sola ejecución coordinada.

### Release y smoke test

- [ ] Registrar versión/commit, ejecutar suite automatizada y revisar el diff de migraciones.
- [ ] Activar modo mantenimiento cuando corresponda, desplegar artefacto, ejecutar migraciones aprobadas y desactivar mantenimiento tras comprobar salud.
- [ ] Probar login, logout, API autenticada, permisos, reservas/pedidos críticos y respuestas de error con cuentas de prueba.
- [ ] Confirmar que los logs no contienen tokens, contraseñas ni datos sensibles; comprobar alertas y procedimiento de rollback.
- [ ] Documentar responsable, ventana, resultado, versión desplegada y evidencia de backup/restauración.

## Verificaciones realizadas

- Se revisaron `.env.example`, exclusión de `.env` en Git, `Dockerfile`, `compose.yaml`, Nginx, configuración de Laravel para aplicación/logs/colas/sesiones/archivos, rutas de consola y uso de jobs.
- No se leyeron los valores del `.env` local. El archivo está ignorado por Git.
- No se ejecutó despliegue, backup, migración productiva ni modificación de infraestructura.
- No se encontraron definiciones de scheduler ni jobs asíncronos en el código examinado.

## Estructura existente revisada

- Aplicación: `app/`, `routes/`, `bootstrap/`, `config/`.
- Contenedores: `Dockerfile`, `compose.yaml`, `docker/nginx/`.
- Configuración de ejemplo: `.env.example`; los archivos `.env` locales están ignorados por Git.
- Evidencia de fases anteriores: `docs/`.

No se agregó infraestructura, un entorno Compose de producción ni dependencias. La decisión de hosting y la topología de producción quedan pendientes de la plataforma destino.
