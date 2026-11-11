# Plan de despliegue — HostalConnect UNAB en Azure VM

> **Estado:** Deployed — VM de demostración desplegada y verificada; la validación previa se completó correctamente.

Generado: 2026-11-11

## 1. Objetivo y alcance

Desplegar la aplicación Laravel/API y MySQL en una VM de Azure de bajo costo, habilitar HTTPS, apuntar Flutter al FQDN público, crear exactamente tres cuentas de demostración y verificar los flujos principales. Publicar en GitHub sin secretos y probar/instalar el APK en el Android conectado.

**Modo:** Modernizar/desplegar el proyecto existente. **Clasificación:** demo/POC de baja concurrencia, no producción crítica ni alta disponibilidad. **Escala:** pequeña (<1K usuarios).

## 2. Contexto y requisitos aprobados

| Atributo | Valor |
|---|---|
| Presupuesto | Sin tope impuesto por el usuario; elegir la opción viable de menor costo disponible |
| Suscripción | Azure for Students, activa en la sesión autenticada (identificadores no se publican en el repositorio) |
| Tenant | El tenant actual de Azure CLI (identificador no versionado) |
| Región | Central US (`centralus`); permitida por `sys.regionrestriction`, con cuota familiar disponible |
| Dominio | FQDN `*.centralus.cloudapp.azure.com` en IP pública estática |
| Persistencia | MySQL en disco administrado persistente; no copiar la BD local |
| Cuentas de demo | Solo `example@unab.edu.co`, `operador@unab.edu.co`, `admin@unab.edu.co`; contraseña común solicitada por el usuario. No versionar la contraseña ni crear cuentas adicionales |
| Cumplimiento | Sin requisito de residencia confirmado; solo datos de demostración |

### Costo mensual consultado (USD, estimado, sin impuestos)

| Recurso | Cálculo | Estimación |
|---|---:|---:|
| VM Linux `Standard_D2s_v3` | $0.11/h × 730 h | $80.30 |
| IP pública estática IPv4 Standard | $0.005/h × 730 h | $3.65 |
| Disco OS Standard SSD E4 LRS (32 GiB) | $4.48/mes | $4.48 |
| **Total estimado** |  | **$88.43/mes** |

Tráfico saliente, operaciones de disco, impuestos y variaciones de precio no están incluidos. Desasignar la VM detiene el cargo de cómputo, pero interrumpe web/API y el móvil no podrá conectarse. El crédito Azure for Students disponible no ha sido comprobado.

## 3. Componentes detectados

| Componente | Tecnología | Ruta |
|---|---|---|
| Backend web/API | Laravel 12, PHP 8.4, Sanctum | `app/`, `routes/`, `resources/` |
| Base de datos | MySQL 8.4 | `database/` |
| Android | Flutter / Dart | `mobile/` |
| Empaquetado actual | Docker, Nginx/PHP-FPM | `Dockerfile.render`, `docker/render/` |

## 4. Receta

**Bicep (despliegue de suscripción) + Azure CLI + Compose en una VM.** Se elige control directo e IaC mínima: no se necesitan ACR, App Service, Log Analytics, Application Insights, Key Vault, Managed Environment ni servicios adicionales con costo. El código se publica en GitHub; la imagen se construye localmente y los secretos se generan en el host, nunca en el repositorio ni en la imagen.

## 5. Arquitectura y seguridad

- Ubuntu Server 24.04 LTS en una `Standard_D2s_v3` (2 vCPU/8 GiB), sin zonas/HA.
- Docker Compose con Laravel web/API, MySQL 8.4 privado en la red Compose y Caddy para HTTPS.
- IP pública estática, DNS de Azure y TLS automático. NSG: solo 80/443 públicos y SSH limitado a la IP autorizada.
- Disco OS Standard SSD LRS de 32 GiB con volumen Docker persistente para datos MySQL; MySQL nunca se expone a Internet.
- Persistencia de secretos con `.env` local a la VM, permisos restringidos; credenciales de DB y `APP_KEY` aleatorias.
- Crear solo tres cuentas de demo en la DB vacía; no ejecutar `DatabaseSeeder`, que genera datos y usuarios adicionales.
- La contraseña común solicitada es débil y conocida: limitar el acceso al demo, no reutilizarla en el SO/DB/GitHub y cambiarla antes de cualquier uso real.
- Logs en Docker/journald locales; backups deben descargarse fuera de la VM y probar restauración. Sin servicio de backup administrado en este plan base.

### Riesgos

- 2 vCPU y 8 GiB permiten ejecutar Laravel y MySQL para la demo; compilar la imagen en el equipo local para evitar usar recursos de la VM.
- Un solo host y discos locales administrados son puntos únicos de falla; no es una arquitectura de producción crítica.
- `Standard_F1as_v7` fue rechazada por Azure (`ResourceNotAvailableForOffer`) al solicitar cuota; B1s/B1ms y D2as v5 están restringidas. `Standard_D2s_v3` en Central US está sin restricción y tiene cuota de familia asignada.

## 6. Inventario y capacidad

| Recurso Azure | Cantidad | Límite/uso/capacidad actual | Estado |
|---|---:|---|---|
| `Microsoft.Compute/virtualMachines` (`Standard_D2s_v3`) | 1 | Central US: `cores` límite 6, uso 0, disponible 6; `standardDSv3Family` límite 4, uso 0, disponible 4 | Disponible; SKU sin restricción de suscripción |
| `Microsoft.Compute/disks` Standard SSD | 1 | Central US: `StandardSSDDiskCount` límite 50,000, uso 0 | Disponible |
| `Microsoft.Network/publicIPAddresses` Standard | 1 | límite 3, uso 0, disponible 3 | Disponible |
| `Microsoft.Network/virtualNetworks`, subnets, NICs, NSG | 1 conjunto | Sin recursos de HostalConnect existentes; cuotas de red no bloquean la IP consultada | Pendiente validar en `azure-validate` |
| Azure Policy `sys.regionrestriction` | — | Central US permitida; regiones permitidas: centralus, canadacentral, eastus, mexicocentral, brazilsouth | Compatible |

Antes del despliegue no existían VMs, discos ni IP públicas del proyecto en Central US; el grupo de recursos creado por esta entrega es independiente y no reemplaza infraestructura ajena.

## 7. Validation Proof

- Status: Passed (pre-deployment).
- Timestamp: `2026-11-11T16:24:49-05:00`.
- Azure account: authenticated as Azure for Students; current user has Owner at subscription scope.
- Region/SKU: `centralus` is policy-allowed; `Standard_D2s_v3` has no SKU restriction, with `cores` 0/6 and `standardDSv3Family` 0/4 used.
- Infrastructure preflight: `az bicep build --file infra/azure-vm/main.bicep --stdout`, `az deployment sub validate`, and `az deployment sub what-if` all passed before deployment; preview reported Create 7, Modify 0, Delete 0.
- Docker: production image `hostalconnect-app:production` built successfully (819,582,180 bytes); `.azure`, IaC and deployment scripts are excluded from the build context and verified absent from the image. Compose config parsed successfully with non-secret validation values.
- Backend: `docker compose exec -T app php artisan test --compact` — 138 passed, 872 assertions. `vendor/bin/pint --test` — all 192 files passed. Local `GET http://localhost:8080/up` returned HTTP 200.
- Flutter: Flutter 3.44.9 `flutter analyze --no-pub` passed; `flutter test --no-pub` passed 72 tests with one staging-dependent test skipped.
- Policy/roles: `sys.regionrestriction` includes Central US. No app managed identity or Azure data-plane integration exists, so no role assignment is required. No Node.js runtime/package is part of this Laravel/Flutter project.
- Azure policy assignments: the subscription has only `sys.regionrestriction`; Central US is an allowed location and no other assigned policy blocks the template.
- Required Bicep recipe helper: `validate-deployment.sh` — OVERALL PASS; CLI/auth/build/validate/what-if all passed, with Create 7, Modify 0, Delete 0. `az bicep lint` passed.
- Commands and logs were run locally; `.azure/validation.parameters.json` contains the SSH public key and source IP but is ignored by Git.
- Deployment: Azure subscription deployment `hostalconnect-20261111212723` succeeded at `2026-11-11T21:28:31Z`; VM `vm-hostalconnect-modzr3g6xybig`, size `Standard_D2s_v3`, resource group `rg-hostalconnect-unab-20261111-cus`. VM provisioning state is `Succeeded`; it has no managed identity or app role assignments.
- Public endpoint: `https://hostalconnect-modzr3g6xybig.centralus.cloudapp.azure.com`; HTTPS `GET /up` returned 200, HTTP redirected to HTTPS (308), and `/` redirected to the web login (302). App, MySQL and Caddy containers are healthy.
- Database: MySQL query returned exactly 3 users; `/tmp/hostalconnect-demo.env` was removed after seeding. Live HTTPS login, `GET /me`, logout, and token revocation passed for `example@unab.edu.co` (`CLIENTE`), `operador@unab.edu.co` (`OPERADOR`) and `admin@unab.edu.co` (`ADMINISTRADOR`).
- Seed recovery: the first seed attempt found a stale Laravel config cache without `DEMO_ACCOUNT_PASSWORD`. Cleared configuration before seeding, rebuilt the cache without the temporary password, and updated the deploy script to do this safely with an exit cleanup trap.
- Android: APK built with `APP_ENV=production` and the Azure API origin, then installed on the connected Xiaomi 22101316G (Android 14). The phone opened a TCP connection to the VM's port 443. ADB input injection is denied by the handset, so in-app form submission could not be automated. Chrome also displayed a Safe Browsing “Sitio peligroso” interstitial for the Azure FQDN; no bypass was attempted.

## 8. Checklist de ejecución

- [x] Usuario autorizó ejecutar buscando la opción viable más barata.
- [x] Suscripción activa, política de región, SKU y precios consultados.
- [x] Android físico conectado por ADB (`22101316G`).
- [x] Usuario autorizó usar cualquier región permitida y confirmó que acepta el costo.
- [x] Confirmar SKU `Standard_D2s_v3` sin restricción y cuota disponible en Central US.
- [x] Preparar Compose/Caddy e IaC/scripts sin secretos; construir e inspeccionar imagen.
- [x] Ejecutar pruebas Laravel y Flutter, validar despliegue local.
- [x] Desplegar DB vacía, migrar y crear exactamente las tres cuentas requeridas.
- [x] Verificar web (`/up`), API, autenticación/roles y TLS; probar que el teléfono alcanza TCP 443.
- [x] Compilar APK debug con entorno `production` y el FQDN HTTPS, e instalarlo por ADB.
- [ ] Completar login visual desde Flutter en el teléfono y resolver/revisar la advertencia Safe Browsing antes de compartir el dominio públicamente.
- [ ] Publicar cambios en `main` sin incluir credenciales/secretos y verificar GitHub Actions.

## 9. Flujo obligatorio

`azure-prepare` → `azure-validate` → `azure-deploy`. No ejecutar la creación de recursos antes de la validación. Si Azure no aprueba cuota/capacidad, detener el deploy y usar otra SKU/región solo tras comprobar precio, política y cuota.

## 10. Validation Checks

All validation checks pass:

- [x] Bicep recipe — core CLI/auth/build/validate/what-if via `validate-deployment.sh`; Create 7, Modify 0, Delete 0.
- [x] Bicep recipe — `az bicep lint --file infra/azure-vm/main.bicep`.
- [x] Bicep recipe — Azure policy assignment review; Central US is allowed and no other assigned policy blocks deployment.
- [x] AZCLI/Compose — production Docker image built; Compose configuration parsed successfully.
- [x] Backend — Laravel tests (138 passed, 872 assertions), Pint (192 files), and local `/up` health endpoint (HTTP 200).
- [x] Flutter — analyze passed; 72 tests passed, one staging-dependent test skipped; no Node.js runtime/package is used.
- [x] Static RBAC review — no managed identity or Azure data-plane integration is used, so no application role assignment is needed.

## 11. Role Assignment Verification

- Status: Verified.
- Identities checked: none; the VM uses SSH key authentication and the application does not access Azure data-plane services.
- Roles confirmed: no application role assignments required.
- Issues: none.
