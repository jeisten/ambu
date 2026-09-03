---
title: "Ambu-U Knowledge Brain - Home MOC"
type: moc
tags:
  - index
  - moc
  - home
  - ambu-u
date: 2026-08-29
status: active
---

# 🧠 Ambu-U: Cerebro de Conocimiento y Arquitectura

Bienvenido al Vault de conocimiento y arquitectura del proyecto **Ambu-U** (API RESTful de Rastreo y Gestión de Flota de Ambulancias en Tiempo Real). Este vault funciona como la fuente única de verdad (Single Source of Truth) para el equipo de desarrollo, arquitectos y agentes autónomos de IA.

---

## 🗺️ Mapas de Contenido (MOCs)

- [[Architecture-MOC]]: Visión general de la arquitectura del sistema, flujo de eventos, WebSockets nativos con Reverb y pipeline de datos.
- [[Roadmap-MOC]]: Fases de desarrollo, estado de entrega de hitos y backlog técnico priorizado.

---

## 📋 Especificaciones del Proyecto

- [[Business-Rules]]: Lógica de negocio, reglas operativas de remisiones, cálculos matemáticos y validaciones.
- [[Data-Dictionary]]: Diccionario completo de datos, modelos Eloquent, migraciones, tipos de datos y relaciones relacionales.
- [[API-Contracts]]: Especificación detallada de endpoints RESTful, payloads, respuestas HTTP y canales WebSocket.

---

## 🏛️ Decisiones de Arquitectura (ADRs)

- [[ADR-001-laravel-11-and-reverb]]: Adopción de Laravel 11.x y Laravel Reverb para el motor de telemetría en tiempo real.
- *Para registrar una nueva decisión, utilizá [[template-adr]].*

---

## 🧩 Módulos del Sistema

| Módulo | Descripción | Estado |
| :--- | :--- | :--- |
| [[Auth-Module]] | Autenticación con Laravel Sanctum, roles (Driver/Admin) y perfil de usuario | `In-Progress` |
| [[Fleet-Ambulances]] | Gestión del parque automotor, control de SOAT/Tecnomecánica y cron de alertas | `Planned` |
| [[Remissions-Tracking]] | Ciclo de vida del traslado de pacientes, ocupantes y cierre de viaje | `Planned` |
| [[Telemetry-Haversine]] | Ingesta de coordenadas GPS, cálculo Haversine de KM y emisión Reverb | `Planned` |

---

## 📅 Bitácora Diaria (Daily Logs)

- [[2026-08-29]]: Inicialización de arquitectura, definición del stack técnico y creación del vault Obsidian.
- *Para crear una nueva entrada diaria, utilizá [[template-daily-log]].*

---

## 📐 Plantillas Disponibles

- [[template-adr]]: Plantilla estándar para Architectural Decision Records.
- [[template-daily-log]]: Plantilla para bitácoras diarias de ingeniería y sesiones con agentes.
- [[template-module]]: Plantilla para especificación de nuevos módulos de dominio.

---

## ⚙️ Guía de Integración

Consultá `OBSIDIAN_SETUP.md` en la raíz del repositorio para instrucciones sobre configuración de plugins, navegación del grafo y sincronización con agentes de IA.
