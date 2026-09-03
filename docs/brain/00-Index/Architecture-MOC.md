---
title: "Architecture Map of Content"
type: moc
tags:
  - architecture
  - moc
  - system-design
  - websockets
  - laravel
date: 2026-08-29
status: active
---

# 🏛️ Architecture MOC: Sistema Ambu-U

El sistema **Ambu-U** es una solución backend construida sobre **Laravel 11.x (PHP 8.2+)** orientada a eventos para el rastreo en tiempo real, gestión operativa y cálculo métrico de flotas de ambulancias y remisiones de pacientes.

---

## 🏗️ Visión General de la Arquitectura

```mermaid
flowchart TD
    subgraph MobileApp["📱 App Móvil (Conductor)"]
        A1[Login / Token Sanctum]
        A2[Iniciar Remisión]
        A3[Emisor GPS Background]
        A4[Finalizar Viaje]
    end

    subgraph BackendAPI["⚡ Laravel 11.x REST API"]
        B1["AuthController (/api/login)"]
        B2["RemissionController (/api/remissions)"]
        B3["LocationTelemetryService"]
        B4["Haversine Calculation Engine"]
        B5["FuelConsumptionCalculator"]
        B6["Task Scheduler (CheckDocuments)"]
    end

    subgraph RealTime["📡 WebSockets Layer"]
        C1["Laravel Reverb Server"]
        C2["Event: LocationUpdated"]
        C3["Channel: remission.{id}"]
    end

    subgraph Persistence["🗄️ Base de Datos (MySQL / MariaDB)"]
        D1[(users)]
        D2[(ambulances)]
        D3[(patients)]
        D4[(remissions)]
        D5[(remission_occupants)]
        D6[(locations)]
    end

    subgraph HospitalDash["🖥️ Dashboard Hospitalario"]
        E1[Live Map Tracking]
        E2[Fleet Stats & SOAT Alerts]
    end

    A1 -->|POST /api/login| B1
    A2 -->|POST /api/remissions| B2
    A3 -->|POST /api/remissions/{id}/location| B3
    A4 -->|PUT /api/remissions/{id}/finish| B2

    B3 --> B4
    B4 -->|Update total_km| D4
    B3 -->|Insert coords| D6
    B3 -->|Broadcast Event| C2
    C2 --> C1
    C1 -->|Pusher Protocol WebSocket| HospitalDash

    B2 --> B5
    B5 -->|Update fuel_consumed & end_time| D4

    B6 -->|Cron Daily| D2
```

---

## 🔑 Pilares Arquitectónicos

1. **API RESTful Stateless**:
   - Autenticación mediante tokens de portador con **Laravel Sanctum**.
   - Respuestas JSON estandarizadas con códigos HTTP semánticos (`200 OK`, `201 Created`, `401 Unauthorized`, `422 Unprocessable Entity`).

2. **Ingesta de Telemetría de Alta Frecuencia**:
   - Endpoint optimizado `POST /api/remissions/{id}/location`.
   - Consulta rápida de la última coordenada previa mediante índices en `locations(remission_id, created_at)`.
   - Pipeline de cálculo incremental de distancia usando [[Telemetry-Haversine]].

3. **Event-Driven Broadcasting con Laravel Reverb**:
   - WebSockets nativos de Laravel sin costos de suscripción por terceros (Pusher/Ably).
   - Evento `LocationUpdated` implementa `ShouldBroadcastNow` / `ShouldBroadcast`.
   - Canales privados autorizados por remisión o canales de monitoreo global. Ver [[ADR-001-laravel-11-and-reverb]].

4. **Persistencia e Integridad Relacional**:
   - Claves foráneas con integridad referencial (`ON DELETE CASCADE` en subtablas como `locations` y `remission_occupants`).
   - Registro de transacciones atómicas en cambios de estado de remisión.
   - Ver [[Data-Dictionary]] y [[Business-Rules]].

5. **Automatizaciones Programadas (Task Scheduling)**:
   - Worker programado para validar vigencia de documentos (SOAT y Tecnomecánica) a 5 días de expirar.

---

## 🔗 Referencias Cruzadas

- [[Home]]: Volver al inicio.
- [[Business-Rules]]: Lógica de cálculo y reglas del dominio.
- [[Data-Dictionary]]: Modelo E/R y descripción de tablas.
- [[API-Contracts]]: Especificación técnica de contratos REST y WebSocket.
- [[Telemetry-Haversine]]: Detalle matemático de la fórmula de Haversine.
