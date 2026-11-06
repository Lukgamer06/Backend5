# Auditoría arquitectónica — HostalConnect UNAB v1.0

**Fase:** 1 — Auditoría arquitectónica
**Fecha:** 2026-11-05
**Alcance:** entidades y relaciones, capas web/API, cliente Flutter, dependencias y documentación arquitectónica disponible.

## 1. Resumen ejecutivo

La implementación Laravel observada sigue, en términos generales, el flujo solicitado:

```text
Route → Form Request → Controller → Policy/Gate → Service → Repository → Model
```

La capa HTTP transforma entradas validadas y entrega respuestas o vistas; los casos de uso y la persistencia se encuentran en Services y Repositories. Las vistas Blade inspeccionadas no contienen consultas ni lógica de negocio. Los modelos definen relaciones Eloquent que corresponden a las claves foráneas observadas en las migraciones.

Flutter utiliza una arquitectura cliente apropiada para su plataforma:

```text
Screen → ChangeNotifier Controller → Feature Repository
       → BaseRepository / ApiClient → Laravel API
```

La configuración y el almacenamiento del token están separados de las pantallas. `ApiClient` envía el token configurado mediante la cabecera Bearer; una revisión posterior confirmó que el aparente valor fijo observado en la salida de herramientas era una máscara de redacción, no el contenido del código.

No se encontró una especificación arquitectónica vigente dentro de `docs/` ni en los archivos rastreados con nombres de arquitectura. Por tanto, este informe contrasta la implementación con la arquitectura obligatoria indicada para esta entrega, pero no puede certificar conformidad con un documento de diseño externo o inexistente.

## 2. Arquitectura observada

### Backend

- **Entidades:** 11 modelos de dominio y relaciones Eloquent; por ejemplo, `Reserva` se relaciona con `Usuario`, `Cliente`, `Mesa` y `Pedido`.
- **Persistencia:** migraciones separadas para las entidades y la tabla de tokens de Sanctum; las relaciones revisadas tienen claves foráneas correspondientes.
- **Web:** rutas protegidas por `auth` y middleware de rol; los Controllers usan Form Requests y delegan operaciones a Services.
- **API:** rutas versionadas bajo `/api/v1`; las rutas protegidas están agrupadas con `auth:sanctum`. Los Controllers usan Requests, Policies/Gate, Services y API Resources.
- **Servicios y repositorios:** servicios coordinan casos de uso; repositorios contienen consultas y persistencia. El registro explícito de Policies se realiza en `AppServiceProvider`.
- **Vistas:** las comprobaciones realizadas no encontraron consultas Eloquent ni acceso a `DB` en Blade. Los bloques `@php` revisados preparan valores de presentación o datos de navegación; las reglas de negocio y autorización se mantienen en el backend.

### Cliente Flutter

- Estructura por funcionalidades para autenticación, catálogo, incidencias, pedidos, pagos y reservas.
- `ChangeNotifier` gestiona el estado de presentación y los repositorios de funcionalidad encapsulan las llamadas REST.
- `ApiClient`, `ApiConfig` y `TokenStorage` centralizan transporte, configuración de ambiente y persistencia segura del token.
- Los pagos están integrados al flujo de pedidos; no requieren una pantalla independiente para respetar la separación por capas.

### Dependencias

- Composer declara Laravel 12 y Sanctum como dependencias de ejecución.
- Flutter declara dependencias para HTTP, almacenamiento seguro, navegación y estado; todas corresponden a usos encontrados en el código inspeccionado.
- No se identificó una dependencia manifiestamente innecesaria en los manifiestos revisados. No se recomienda agregar paquetes para resolver los hallazgos de esta fase.

## 3. Hallazgos

| ID | Prioridad | Hallazgo | Evidencia e impacto | Recomendación |
|---|---|---|---|---|
| ARQ-01 | P1 — trazabilidad | No hay un documento de arquitectura vigente rastreado en el repositorio. | `docs/` contiene validación de API y artefactos Postman, pero no un documento que establezca límites, dependencias o diagramas de las capas. No puede demostrarse la alineación con la “arquitectura documentada” del requisito. | Conservar este informe como registro de la arquitectura observada y definir/actualizar el documento de referencia en la fase de documentación. |
| ARQ-02 | P2 — documentación inconsistente | El README describe una etapa anterior y contradice el estado actual. | `README.md` afirma que la raíz carece de rutas/pantalla de aplicación y que la entrega se limita a base y enums; el repositorio ya contiene rutas web/API, modelos, servicios, vistas y cliente móvil. | Actualizarlo durante la fase de documentación, una vez confirmados el alcance y los comandos de despliegue. |
| ARQ-03 | P2 — documentación inconsistente | La validación API conserva una lista de Flutter completamente pendiente pese a que ya hay implementación Flutter. | `docs/api-v0.3-validation.md` enumera tareas pendientes de cliente HTTP, almacenamiento de token y flujos que ya aparecen en `mobile/lib`; debe revisarse su estado. | Separar lo implementado de lo pendiente y enlazar pruebas verificables; no asumir que la lista antigua demuestra el estado actual. |
| ARQ-04 | P3 — acoplamiento mejorable | Services reciben clases concretas de Repository en vez de contratos. | La cadena de capas está respetada, pero la dependencia directa limita el reemplazo de implementaciones y el aislamiento de ciertos tests. | Mantener el diseño actual mientras cumpla su propósito. Introducir interfaces solo si una necesidad real de sustitución/testing lo justifica; no se recomienda una refactorización masiva para v1.0. |

## 4. Evaluación de conformidad

| Área | Resultado | Observación |
|---|---|---|
| Entidades y relaciones | Conforme en la revisión estática realizada | Modelos y relaciones Eloquent corresponden con las claves foráneas inspeccionadas. No se propone modificar el esquema. |
| Controller → Request → Service → Repository → Model | Conforme en general | Los flujos web/API revisados conservan la separación; los Controllers no ejecutan consultas de negocio directamente. |
| API REST | Conforme estructuralmente | API versionada, autenticación Sanctum, Requests, autorización y Resources separados. |
| Blade | Conforme en la muestra revisada | Sin consultas ni lógica de negocio detectadas; el código PHP localizado es de presentación. |
| Flutter | Conforme estructuralmente | Arquitectura por funcionalidades y repositorios; el cliente envía el token Bearer configurado. |
| Dependencias | Sin observación crítica | No se recomienda agregar dependencias. |
| Documentación de arquitectura | No verificable contra baseline | Falta una especificación arquitectónica de referencia y hay documentos desactualizados. |

## 5. Propuesta de arquitectura y estructura a conservar

No se propone rediseñar el sistema. Se conserva la estructura existente:

```text
app/
  DTOs/
  Enums/
  Http/
    Controllers/{web,Api/V1}/
    Requests/{web,Api/V1}/
    Resources/Api/V1/
  Models/
  Policies/
  Repositories/
  Services/
mobile/lib/
  core/{network,storage}/
  features/<funcionalidad>/{models,providers,repositories,screens}/
  routes/
database/
  migrations/
```

El backend debe conservar las reglas de dominio y coordinación en Services, las consultas/persistencia en Repositories, la validación en Requests y la autorización en Policies/Gate. Flutter debe seguir siendo consumidor de la API; sus controles de interfaz no reemplazan la autorización del servidor.

### Riesgos de seguridad observados en el alcance arquitectónico

- La navegación por rol y el estado de sesión del cliente son presentación; los permisos efectivos deben seguir aplicándose en Laravel.
- La revisión específica de fase 3 verificó autenticación, ownership, CSRF, validación y permisos; no encontró fallos demostrables en las superficies revisadas.

## 6. Validaciones realizadas

- Inventario de rutas, modelos, migraciones, Controllers, Requests, Services, Repositories, Policies, DTOs y estructura Flutter.
- Revisión estática de flujos representativos web/API y de configuración de dependencias.
- Búsqueda en Controllers y vistas para detectar consultas o persistencia fuera de Repositories.
- Contraste del token Flutter con el proveedor de almacenamiento y el contrato de rutas Sanctum.
- Verificación de documentos de arquitectura rastreados en `docs/`.

No se ejecutaron pruebas funcionales ni de integración para evitar adelantar las fases 4 y 5. Tampoco se realizaron cambios de código o esquema.

## 7. Decisión de fase

- **Resultado:** auditoría arquitectónica completada con hallazgos accionables.
- **Archivos modificados:** únicamente este informe.
- **Archivos de código modificados:** ninguno.
- **Dependencias agregadas:** ninguna.
- **Modelo de datos modificado:** no.
- **Siguiente paso:** detenerse aquí. Antes de iniciar la fase 2 se debe revisar este informe y confirmar explícitamente el commit y push de la fase 1, conforme al control de versiones solicitado.
