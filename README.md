# HostalConnect UNAB

Aplicación Laravel 12 para PHP 8.4 con MySQL 8.4, PHP-FPM y Nginx.

## Requisitos

- Docker Engine
- Docker Compose v2

## Inicio local

```bash
cp .env.example .env
docker compose up --build -d
docker compose exec app php artisan key:generate
```

La aplicación queda disponible en `http://localhost:8080`. El endpoint `/up`
indica si Laravel está respondiendo. La raíz aún no tiene una pantalla ni rutas
de aplicación porque esta entrega se limita a la base del proyecto y los enums.

MySQL solo es accesible dentro de la red de Compose. Los datos se conservan en
el volumen `mysql_data`. Las credenciales de `.env.example` son exclusivamente
para desarrollo local: reemplázalas antes de usar cualquier entorno compartido.
En Linux con SELinux, el montaje del código usa la etiqueta compartida `:z`.

Para apagar los servicios sin borrar los datos:

```bash
docker compose down
```

La eliminación de datos requiere retirar explícitamente el volumen:

```bash
docker compose down --volumes
```
