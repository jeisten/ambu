---
title: "Guia de Arranque - Ambu-U desde Cero"
type: "guide"
status: "active"
date: 2026-08-29
tags:
  - ambu-u
  - setup
  - commands
links:
  - "[[Home]]"
---

# Guia de Arranque Completo - Ambu-U

Sigue estos pasos EN ORDEN para levantar el proyecto desde cero.

---

## PASO 1 — Prerequisitos

Asegurate de tener instalado en tu maquina:
- **PHP 8.2+**: `php -v`
- **Composer 2.x**: `composer --version`
- **MySQL 8.x**: corriendo en puerto 3306
- **Node.js 18+** (solo si vas a frontend): `node -v`

---

## PASO 2 — Clonar y Configurar el Entorno

```bash
# 1. Copiar el archivo de entorno
cp .env.example .env

# 2. Abrir .env y completar estos valores:
#    DB_PASSWORD=tu_password_de_mysql
#    DEEPSEEK_API_KEY=sk-xxxxxxxxxxxx   <- de platform.deepseek.com
```

---

## PASO 3 — Crear la Base de Datos MySQL

Abre tu cliente MySQL (Workbench, Heidisql, TablePlus o consola) y ejecuta:

```sql
CREATE DATABASE ambu_u CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

---

## PASO 4 — Instalar Dependencias PHP

```bash
composer update --no-interaction
```

> Esto descarga Laravel 11, Sanctum, Reverb y todas las dependencias al directorio vendor/.

---

## PASO 5 — Generar la Clave de la Aplicacion

```bash
php artisan key:generate
```

> Esto llena automaticamente APP_KEY en tu .env. Sin esto, Laravel no arranca.

---

## PASO 6 — Ejecutar las Migraciones

```bash
php artisan migrate
```

Esto crea en MySQL las tablas:
- users
- ambulances
- patients
- remissions
- remission_occupants
- locations
- personal_access_tokens (Sanctum)

---

## PASO 7 — Ejecutar los Seeders (Usuarios iniciales)

```bash
php artisan db:seed
```

Esto crea en la base de datos:

| Usuario | Email | Password | Rol |
|---------|-------|----------|-----|
| Administrador General | admin@ambuu.com | Admin123* | admin |
| Carlos Conductor | conductor@ambuu.com | Driver123* | driver |

---

## PASO 8 — Levantar los Servidores (3 terminales)

Abris 2 terminales en la carpeta del proyecto:

### Terminal 1 — API REST Laravel
```bash
php artisan serve
# Disponible en: http://localhost:8000
```

### Terminal 2 — Servidor WebSockets (Laravel Reverb)
```bash
php artisan reverb:start --debug
# Disponible en: ws://localhost:8080
```

### (Opcional) Terminal 3 — Worker de Colas
Si en el futuro usas QUEUE_CONNECTION=database en lugar de sync:
```bash
php artisan queue:work
```

---

## PASO 9 — Probar la API con Postman

1. Abre **Postman**.
2. Hacé click en **Import**.
3. Selecciona el archivo: `docs/Ambu-U.postman_collection.json`
4. La coleccion se importa con la variable `base_url = http://localhost:8000/api`.
5. Ejecuta primero **"Login (Admin)"** — el token se guarda automaticamente en la variable `{{token}}`.
6. Ahora podes ejecutar cualquier otro endpoint.

---

## PASO 10 — Comando de Alertas de Vencimiento (Cron manual)

Para probar el cron de SOAT/Tecnomecanica manualmente:

```bash
php artisan ambulances:check-expiring-docs
# Con parametro personalizado (10 dias):
php artisan ambulances:check-expiring-docs --days=10
```

---

## Resumen de Comandos de Uso Frecuente

```bash
# Refrescar toda la BD y volver a sembrar (CUIDADO: borra todos los datos)
php artisan migrate:fresh --seed

# Ver todas las rutas de la API registradas
php artisan route:list --path=api

# Limpiar cache de configuracion
php artisan config:clear
php artisan cache:clear

# Ver logs en tiempo real
tail -f storage/logs/laravel.log          # Linux/Mac
Get-Content storage/logs/laravel.log -Wait # Windows PowerShell
```

---

## Variables del .env que vos completas

| Variable | Donde conseguirla |
|----------|-------------------|
| `DB_PASSWORD` | Tu instalacion local de MySQL |
| `APP_KEY` | Se genera sola con `php artisan key:generate` |
| `DEEPSEEK_API_KEY` | https://platform.deepseek.com/api-keys |
