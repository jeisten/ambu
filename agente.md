# 🏥 Documento de Arquitectura y Requerimientos: API RESTful Rastreo de Ambulancias en Tiempo Real

## 1. Stack Tecnológico y Entorno
* **Framework Backend:** Laravel 11.x (PHP 8.2+)
* **Autenticación:** Laravel Sanctum (Tokens API para la App Móvil).
* **WebSockets (Tiempo Real):** Laravel Reverb (Nativo, sin dependencias de terceros como Pusher).
* **Base de Datos:** MySQL 8.x / MariaDB.
* **Paradigma:** API RESTful orientada a eventos.

## 2. Dependencias y Librerías a Instalar
El agente debe inicializar el proyecto y requerir los siguientes paquetes mediante Artisan/Composer:
1. `php artisan install:api` (Instala Laravel Sanctum y prepara rutas `/api`).
2. `php artisan install:broadcasting` (Instala **Laravel Reverb** para WebSockets).
3. `composer require guzzlehttp/guzzle` (Para futuras integraciones de IA con OpenAI/Vertex AI).
4. Configurar `.env` para `BROADCAST_CONNECTION=reverb`.

---

## 3. Esquema de Base de Datos (Modelos y Migraciones)

A continuación, la estructura relacional estricta. El agente debe generar migraciones con estas especificaciones:

### Tabla: `users` (Extendida para Conductores / Login)
Gestiona el acceso a la app móvil.
* `id` (PK, bigint, auto_increment)
* `name` (string, Nombre completo)
* `email` (string, unique, Login)
* `password` (string, Hash)
* `phone` (string)
* `id_number` (string, unique, Cédula del conductor)
* `blood_type` (enum: 'A+','A-','B+','B-','AB+','AB-','O+','O-')
* `role` (enum: 'driver', 'admin') -> *Default: driver*
* `timestamps`

### Tabla: `ambulances` (Gestión de Flota)
* `id` (PK, bigint)
* `plate` (string, unique, Placa)
* `km_per_gallon` (decimal 8,2, Factor para calcular consumo de gasolina)
* `tecnomecanica_issue_date` (date)
* `tecnomecanica_expiry_date` (date)
* `soat_issue_date` (date)
* `soat_expiry_date` (date)
* `status` (enum: 'active', 'maintenance', 'inactive')
* `timestamps`

### Tabla: `patients` (Registro de Pacientes)
* `id` (PK, bigint)
* `id_type` (enum: 'CC', 'TI', 'CE', 'RC', 'PAS')
* `id_number` (string, unique)
* `full_name` (string)
* `regime` (enum: 'Contributivo', 'Subsidiado', 'Particular', 'Vinculado')
* `eps_name` (string)
* `is_soat_case` (boolean, default: false)
* `soat_company` (string, nullable, Empresa aseguradora si aplica)
* `timestamps`

### Tabla: `remissions` (Núcleo: Viajes/Traslados)
Agrupa toda la operación para generar estadísticas e historial.
* `id` (PK, bigint)
* `driver_id` (FK -> users.id)
* `ambulance_id` (FK -> ambulances.id)
* `patient_id` (FK -> patients.id)
* `status` (enum: 'en_camino', 'trasladando', 'finalizado')
* `is_out_of_city` (boolean, default: false, Remisión fuera de la ciudad)
* `observations` (text, nullable)
* `total_kilometers` (decimal 10,2, default: 0.00)
* `fuel_consumed_gallons` (decimal 10,2, default: 0.00)
* `start_time` (timestamp, nullable)
* `end_time` (timestamp, nullable)
* `timestamps`

### Tabla: `remission_occupants` (Control de Ocupantes)
Tripulación o acompañantes extra en el viaje.
* `id` (PK, bigint)
* `remission_id` (FK -> remissions.id, OnDelete: cascade)
* `full_name` (string)
* `role` (enum: 'Médico', 'Enfermero', 'Familiar', 'Estudiante', 'Otro')
* `timestamps`

### Tabla: `locations` (Telemetría / Tiempo Real)
Recibe las tramas GPS de la app.
* `id` (PK, bigint)
* `remission_id` (FK -> remissions.id, OnDelete: cascade)
* `latitude` (decimal 10,8)
* `longitude` (decimal 11,8)
* `speed` (decimal 8,2, nullable)
* `created_at` (timestamp, indexado para consultas rápidas)

---

## 4. Lógica de Negocio y Automatizaciones (Backend Inteligente)

El agente debe implementar la siguiente lógica dentro de los Controladores/Servicios de Laravel:

1. **Cálculo de Kilometraje en Tiempo Real (Fórmula de Haversine):**
   * Al recibir un `POST` en `/api/remissions/{id}/location`, el sistema busca la **última coordenada guardada** de ese `remission_id`.
   * Calcula la distancia en KM entre la coordenada anterior y la nueva usando la fórmula de Haversine.
   * Suma esta distancia al campo `total_kilometers` de la tabla `remissions`.
   * Guarda la nueva coordenada en la tabla `locations`.

2. **WebSockets (Laravel Reverb):**
   * Tras guardar la ubicación, disparar el evento `LocationUpdated implements ShouldBroadcast`.
   * Canal recomendado: `PrivateChannel('remission.{id}')` o canal público para el dashboard del hospital.

3. **Cálculo de Combustible (Al finalizar viaje):**
   * En el endpoint de finalizar viaje, tomar `total_kilometers` de la remisión y dividirlo por el `km_per_gallon` de la ambulancia. Guardar el resultado en `fuel_consumed_gallons`.

4. **Alertas de Vencimiento SOAT/Tecnomecánica (Task Scheduling):**
   * Crear un comando de consola (`php artisan make:command CheckDocuments`).
   * Lógica: `Ambulance::whereRaw('DATEDIFF(soat_expiry_date, NOW()) <= 5')->get();`.
   * Ejecución: Registrar en `routes/console.php` para que corra `->daily()`.
   * Acción: Disparar evento/notificación a la tabla de base de datos o email a administradores.

---

## 5. Arquitectura de Endpoints (Rutas)

Todas protegidas bajo `middleware('auth:sanctum')`, excepto login.

**Auth:**
* `POST /api/login` (Recibe email/password, retorna Token).
* `POST /api/logout`

**Gestión Operativa:**
* `GET /api/ambulances` (Lista ambulancias activas).
* `POST /api/patients` (Busca por cédula o crea uno nuevo).

**Flujo de la Remisión:**
* `POST /api/remissions` (Inicia viaje. Requiere: ambulance_id, patient_id, is_out_of_city, array de occupants).
* `POST /api/remissions/{id}/location` (Recibe: lat, lng, speed. Ejecuta Haversine + Broadcast WebSocket).
* `PUT /api/remissions/{id}/finish` (Calcula gasolina, cierra status y estampa `end_time`).

**Dashboard / Estadísticas:**
* `GET /api/stats/ambulances/{id}` (Retorna total km recorridos, galones consumidos, remisiones fuera vs dentro de la ciudad).

---

## 6. Oportunidades de Integración IA (Opcional para el Agente)
Si se habilita módulo IA, el agente debe prever:
* Un servicio `AiPatientAnalysisService` que, al guardar un paciente con observaciones, consuma la API de OpenAI para categorizar la urgencia clínica basándose en texto natural, generando un flag de `urgency_level` en la remisión.

---

## 7. Memoria Continua y Cerebro de Conocimiento (Obsidian Vault)

El repositorio cuenta con una base de conocimiento viva en `docs/brain/` diseñada para ser abierta como Vault en Obsidian (ver `OBSIDIAN_SETUP.md`):

* **Índice y MOCs:** `docs/brain/00-Index/` (`Home.md`, `Architecture-MOC.md`, `Roadmap-MOC.md`).
* **Especificaciones del Sistema:** `docs/brain/01-Project-Specs/` (`Business-Rules.md`, `Data-Dictionary.md`, `API-Contracts.md`).
* **Decisiones de Arquitectura:** `docs/brain/02-ADR/` (iniciando con `ADR-001-laravel-11-and-reverb.md`).
* **Bitácora Diaria:** `docs/brain/03-Daily-Logs/` (`2026-08-29.md` y sucesivos).
* **Módulos de Dominio:** `docs/brain/04-Modules/` (`Auth-Module.md`, `Fleet-Ambulances.md`, `Remissions-Tracking.md`, `Telemetry-Haversine.md`).
* **Plantillas:** `docs/brain/05-Templates/` (`template-adr.md`, `template-daily-log.md`, `template-module.md`).

**Protocolo para el Agente:**
Antes de emprender cambios mayores o refactors, el agente debe consultar las especificaciones en `docs/brain/`, registrar nuevas decisiones técnicas en `02-ADR/` y actualizar la bitácora o contratos según corresponda.