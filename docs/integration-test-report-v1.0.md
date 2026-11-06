# Pruebas de integración — HostalConnect UNAB v1.0

**Fase:** 5 — Pruebas de integración
**Fecha:** 2026-11-05
**Entorno:** Docker Compose aislado (`hostalconnect-phase5`), Nginx local en puerto 18080, Laravel/PHP-FPM y MySQL 8.4.

## 1. Resultado

La integración **Flutter → API → Laravel → MySQL no se ejecutó como una sola prueba automatizada con el binario Flutter**. Sí se validaron por separado:

1. Los contratos y flujos Flutter con la suite Mobile CI (HTTP simulado), para el SHA remoto `ef93d12182706744e20602e40e9b11e008422725`: análisis sin problemas, 72 pruebas aprobadas y 1 omitida.
2. Laravel/API sobre HTTP real, conectado a un MySQL aislado: login, token Sanctum, lecturas, creación/lectura/cancelación de reserva, permisos, validación y revocación.
3. La suite Feature/Unit de Laravel en el contenedor construido: **136 pruebas aprobadas, 863 assertions**. Esa suite usa SQLite en memoria; no se presenta como prueba del motor MySQL.

En consecuencia, se verificó la parte API → Laravel → MySQL de extremo a extremo y la conformidad del cliente Flutter mediante sus pruebas de contrato, pero queda pendiente ejecutar el cliente Flutter real contra esta API/MySQL.

## 2. Flujo API → Laravel → MySQL ejecutado

Se levantó un proyecto Compose con nombre y volúmenes aislados, sin reutilizar la base de desarrollo. Después de aplicar las migraciones a su MySQL vacío, se creó una cuenta de prueba efímera y se ejecutaron solicitudes HTTP locales:

| Operación | Resultado |
|---|---|
| `GET /up` | HTTP 200 |
| Login de cuenta de prueba | HTTP 200; token Sanctum emitido |
| `GET /api/v1/me` con Bearer | HTTP 200 |
| `GET /api/v1/productos` | HTTP 200 |
| `GET /api/v1/reservas` | HTTP 200 |
| Consulta de disponibilidad | HTTP 200 |
| Crear reserva | HTTP 201 |
| Volver a consultar reservas | HTTP 200; reserva creada presente en la colección, confirmando lectura tras escritura |
| Cancelar reserva | HTTP 200; estado `CANCELADA` |
| `GET /api/v1/incidencias` como cliente | HTTP 403 |
| `POST /api/v1/reservas` con payload vacío | HTTP 422; `meta.errors` presente |
| Logout | HTTP 200 |
| Reutilizar token revocado en `/api/v1/me` | HTTP 401 |
| Login con credenciales inválidas | HTTP 401 con envoltorio JSON estándar |
| `/api/v1/me` sin autenticación | HTTP 401 con envoltorio JSON estándar |

El primer login inválido previo a las migraciones respondió 500 porque la base aislada aún no tenía tablas. Tras migrar, la misma solicitud respondió 401. No se modificó código para este comportamiento.

## 3. Consistencia de datos, validaciones y errores

- [x] La migración creó las tablas de dominio y de tokens en el MySQL aislado.
- [x] La reserva escrita se recuperó posteriormente a través de la API.
- [x] Los cambios de estado de cancelación y revocación del token se reflejaron en solicitudes posteriores.
- [x] El API devolvió las envolturas JSON esperadas para 401, 403, 422 y las respuestas exitosas.
- [x] La prueba de permisos confirmó que una cuenta CLIENTE no puede consultar incidencias restringidas al personal.
- [ ] No se probó carga ni concurrencia de escritura.
- [ ] No se ejecutó el binario de Flutter conectado al servidor local; el Mobile CI usa pruebas unitarias/flujo con transporte simulado y el live smoke depende de staging.

## 4. Limpieza y seguridad del entorno

- La base y la cuenta de prueba fueron efímeras, creadas solo en el proyecto Compose aislado.
- El puerto utilizado fue `18080` para evitar interferir con el puerto configurado por defecto.
- Se ejecutó `docker compose -p hostalconnect-phase5 down --volumes --remove-orphans` al finalizar. Los contenedores, red y volúmenes de prueba se eliminaron.
- No se guardaron credenciales de staging ni datos de prueba en archivos rastreados.

## 5. Cierre de fase

- **Problemas de integración corregidos:** ninguno; el 500 inicial se explicó por el esquema aún no migrado en una base vacía.
- **Archivos de aplicación modificados:** ninguno.
- **Archivo generado:** este informe.
- **Resultado:** integración API/Laravel/MySQL aprobada para el flujo descrito; integración directa del binario Flutter queda pendiente de un entorno Flutter con endpoint y credenciales configurables.
- **Siguiente paso:** detenerse aquí. No iniciar optimización hasta que esta fase haya sido revisada y los commits/push se hayan resuelto según el flujo acordado.
