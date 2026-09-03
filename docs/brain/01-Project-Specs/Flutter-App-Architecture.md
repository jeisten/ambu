---
title: "Arquitectura App Flutter - Ambu-U"
type: "spec"
status: "draft"
date: 2026-08-30
tags:
  - ambu-u
  - flutter
  - mobile
  - architecture
links:
  - "[[Home]]"
  - "[[API-Contracts]]"
---

# Arquitectura y Diseño de la App Flutter (Ambu-U)

## 1. Patrón de Arquitectura
Se recomienda **Clean Architecture** junto con **Riverpod** para la inyección de dependencias y gestión de estado. Esto permite separar la lógica de negocio (dominio), el consumo de la API (datos) y la UI (presentación).

## 2. Stack de Librerías (Dependencies)

| Propósito | Paquete (pub.dev) | Justificación |
|---|---|---|
| **Peticiones HTTP** | `dio` | Interceptores potentes para inyectar el Token de Sanctum y manejar errores globales (ej. 401 Unauthorized). |
| **Estado y DI** | `flutter_riverpod` | Estándar moderno, seguro en tiempo de compilación y excelente para manejar estados asíncronos (cargas de API). |
| **Mapas** | `google_maps_flutter` | Renderizado nativo de Google Maps. Excelente rendimiento para mostrar las ambulancias. |
| **GPS (Foreground)** | `geolocator` | Obtención de latitud, longitud, velocidad y rumbo (heading) con alta precisión. |
| **GPS (Background)** | `flutter_background_geolocation` | **Crucial:** Para que la app del conductor siga enviando GPS a la API aunque apague la pantalla o minimice la app. |
| **WebSockets (Reverb)** | `laravel_echo` + `pusher_client` | Escuchar eventos de Laravel Reverb en tiempo real. |
| **Conectividad** | `connectivity_plus` + `internet_connection_checker` | Detectar si se corta el 4G/5G y guardar coordenadas offline para sincronizar después. |
| **Almacenamiento Local** | `shared_preferences` / `isar` | Guardar token de sesión y datos offline temporales. |

## 3. Estructura de Perfiles y Pantallas (Routing)

La app utilizará `go_router` para manejar la navegación basada en el rol del usuario autenticado.

### 🔐 Flujo Común
- **Splash Screen:** Verifica si hay token guardado.
- **Login Screen:** Pide email/password -> `POST /api/login`. Guarda el token y el rol.

### 🚑 Perfil: Conductor / Paramédico (Driver)
El enfoque es operativo. Pantallas grandes, botones accesibles.
1. **Home (Dashboard Operativo):** Lista de remisiones asignadas en estado `en_camino`.
2. **Pantalla de Viaje Activo (Active Trip):**
   - Mapa centrado en la ubicación actual.
   - Datos del paciente y destino.
   - Botones de acción: `Iniciar Traslado` -> `Finalizar Remisión`.
   - **Background Service:** Ciclo cada X segundos que lee `geolocator` y hace `POST /api/remissions/{id}/location`.
3. **Módulo de IA Clínica (Triage):**
   - Formulario / Input de voz para dictar la observación clínica.
   - Botón "Analizar con IA" -> llama a `POST /api/ai/analyze-observation`.
   - Muestra tarjeta roja/naranja/verde según el Triage devuelto por DeepSeek.

### 🏢 Perfil: Administrador (Admin)
El enfoque es de monitoreo global.
1. **Mapa de Flota (Global Tracking):**
   - Mapa general mostrando todas las ambulancias `in_service`.
   - Se conecta a Laravel Echo `Broadcast::channel('ambulance.{id}')` o `remission.{id}` para mover los pines en el mapa en tiempo real sin recargar.
2. **Gestión de Remisiones:** Historial de viajes, kilómetros recorridos, combustible calculado.
3. **Métricas y Alertas:** Consumo de endpoint `/api/stats/fleet`. Alertas de SOAT/Tecnomecánica próximos a vencer.

## 4. Estrategia de Conectividad (Offline-First Parcial)

Si la ambulancia entra en zona rural sin señal:
1. `connectivity_plus` detecta la caída.
2. En lugar de hacer `POST /api/remissions/{id}/location` y fallar, la app guarda las coordenadas localmente (SQLite/Isar).
3. Al recuperar señal, un *worker* procesa la cola y envía todas las ubicaciones en batch a la API.
