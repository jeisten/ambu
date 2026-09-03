---
title: "API Contracts & Endpoints Specification"
type: spec
tags:
  - specs
  - api
  - rest
  - contracts
  - sanctum
  - reverb
date: 2026-08-29
status: approved
---

# 📡 API Contracts & Endpoints Specification: Ambu-U

Documentación OpenAPI/REST de todos los endpoints del backend, convenciones de payload, códigos de estado HTTP y contratos de eventos WebSocket en **Laravel Reverb**.

---

## 🔐 Convenciones Generales
- **Formato:** JSON (`Content-Type: application/json`, `Accept: application/json`).
- **Autenticación:** `Authorization: Bearer <token_sanctum>` para todas las rutas excepto `/api/login`.
- **Estructura Estándar de Respuesta:**
```json
{
  "success": true,
  "message": "Operación exitosa",
  "data": {}
}
```
- **Estructura de Error (`422 Unprocessable Entity`):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field_name": ["Mensaje de validación."]
  }
}
```

---

## 🚪 Módulo de Autenticación

### 1. Iniciar Sesión (Login)
- **Ruta:** `POST /api/login`
- **Público:** Sí
- **Request Body:**
```json
{
  "email": "conductor@ambu-u.com",
  "password": "Password123*"
}
```
- **Respuesta Exitosa (`200 OK`):**
```json
{
  "success": true,
  "token": "1|qW8YxO9...sanctum_plain_text_token...",
  "user": {
    "id": 1,
    "name": "Carlos Rodríguez",
    "email": "conductor@ambu-u.com",
    "id_number": "1098765432",
    "blood_type": "O+",
    "role": "driver"
  }
}
```

### 2. Cerrar Sesión (Logout)
- **Ruta:** `POST /api/logout`
- **Auth:** `auth:sanctum`
- **Respuesta Exitosa (`200 OK`):**
```json
{
  "success": true,
  "message": "Sesión cerrada correctamente y token revocado"
}
```

---

## 🚑 Módulo Operativo (Flota y Pacientes)

### 3. Listar Ambulancias Activas
- **Ruta:** `GET /api/ambulances`
- **Auth:** `auth:sanctum`
- **Respuesta Exitosa (`200 OK`):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "plate": "UBL-456",
      "km_per_gallon": "32.50",
      "status": "active",
      "soat_expiry_date": "2027-01-15",
      "tecnomecanica_expiry_date": "2027-02-20"
    }
  ]
}
```

### 4. Buscar o Crear Paciente
- **Ruta:** `POST /api/patients`
- **Auth:** `auth:sanctum`
- **Request Body:**
```json
{
  "id_type": "CC",
  "id_number": "1023456789",
  "full_name": "María González",
  "regime": "Contributivo",
  "eps_name": "Sura EPS",
  "is_soat_case": false,
  "soat_company": null
}
```
- **Respuesta Exitosa (`201 Created` / `200 OK`):**
```json
{
  "success": true,
  "data": {
    "id": 42,
    "id_type": "CC",
    "id_number": "1023456789",
    "full_name": "María González",
    "regime": "Contributivo",
    "eps_name": "Sura EPS",
    "is_soat_case": false,
    "soat_company": null,
    "created_at": "2026-08-29T18:00:00.000000Z"
  }
}
```

---

## 📍 Módulo de Remisiones y Telemetría

### 5. Iniciar Remisión (Crear Traslado)
- **Ruta:** `POST /api/remissions`
- **Auth:** `auth:sanctum`
- **Request Body:**
```json
{
  "ambulance_id": 1,
  "patient_id": 42,
  "is_out_of_city": false,
  "observations": "Paciente consciente, dolor torácico agudo",
  "occupants": [
    {
      "full_name": "Dr. Fernando Ortiz",
      "role": "Médico"
    },
    {
      "full_name": "Laura Díaz",
      "role": "Enfermero"
    }
  ]
}
```
- **Respuesta Exitosa (`201 Created`):**
```json
{
  "success": true,
  "data": {
    "id": 105,
    "driver_id": 1,
    "ambulance_id": 1,
    "patient_id": 42,
    "status": "en_camino",
    "is_out_of_city": false,
    "observations": "Paciente consciente, dolor torácico agudo",
    "total_kilometers": "0.00",
    "fuel_consumed_gallons": "0.00",
    "start_time": "2026-08-29T18:15:00.000000Z",
    "end_time": null,
    "occupants": [
      {
        "id": 12,
        "remission_id": 105,
        "full_name": "Dr. Fernando Ortiz",
        "role": "Médico"
      }
    ]
  }
}
```

### 6. Enviar Coordenada GPS (Telemetría en Tiempo Real)
- **Ruta:** `POST /api/remissions/{id}/location`
- **Auth:** `auth:sanctum`
- **Request Body:**
```json
{
  "latitude": 4.60971000,
  "longitude": -74.08175000,
  "speed": 48.50
}
```
- **Respuesta Exitosa (`200 OK`):**
```json
{
  "success": true,
  "message": "Ubicación registrada y transmitida",
  "data": {
    "remission_id": 105,
    "current_location": {
      "latitude": 4.60971000,
      "longitude": -74.08175000,
      "speed": 48.50,
      "created_at": "2026-08-29T18:18:22.000000Z"
    },
    "delta_distance_km": 0.42,
    "accumulated_total_kilometers": "12.85"
  }
}
```

### 7. Finalizar Remisión
- **Ruta:** `PUT /api/remissions/{id}/finish`
- **Auth:** `auth:sanctum`
- **Request Body:** *(Opcional: observaciones de entrega)*
```json
{
  "observations_closing": "Entrega exitosa en urgencias Clínica Central"
}
```
- **Respuesta Exitosa (`200 OK`):**
```json
{
  "success": true,
  "message": "Remisión finalizada exitosamente",
  "data": {
    "id": 105,
    "status": "finalizado",
    "total_kilometers": "24.50",
    "fuel_consumed_gallons": "0.75",
    "start_time": "2026-08-29T18:15:00.000000Z",
    "end_time": "2026-08-29T18:55:00.000000Z"
  }
}
```

---

## 📈 Módulo de Estadísticas

### 8. Estadísticas de Ambulancia
- **Ruta:** `GET /api/stats/ambulances/{id}`
- **Auth:** `auth:sanctum`
- **Respuesta Exitosa (`200 OK`):**
```json
{
  "success": true,
  "data": {
    "ambulance_id": 1,
    "plate": "UBL-456",
    "total_trips": 18,
    "total_kilometers": "480.20",
    "total_fuel_consumed_gallons": "14.77",
    "trips_in_city": 14,
    "trips_out_of_city": 4
  }
}
```

---

## 📡 Contrato de WebSockets (Laravel Reverb)

- **Servidor:** Laravel Reverb (Port `8080`, protocolo compatible con Pusher).
- **Canal Privado:** `private-remission.{remission_id}`
- **Evento Transmitido:** `App\Events\LocationUpdated`
- **Payload del Evento:**
```json
{
  "remission_id": 105,
  "driver": {
    "id": 1,
    "name": "Carlos Rodríguez"
  },
  "ambulance": {
    "id": 1,
    "plate": "UBL-456"
  },
  "location": {
    "latitude": 4.60971000,
    "longitude": -74.08175000,
    "speed": 48.50,
    "timestamp": "2026-08-29T18:18:22Z"
  },
  "total_kilometers": 12.85
}
```

---

## 🔗 Referencias Cruzadas

- [[Business-Rules]]: Validaciones de negocio y flujos.
- [[Data-Dictionary]]: Tipos de datos y estructura de campos.
- [[ADR-001-laravel-11-and-reverb]]: Decisión de Laravel Reverb.
