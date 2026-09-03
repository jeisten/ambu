---
title: "ADR-001: Adopción de Laravel 11.x y Laravel Reverb para Telemetría en Tiempo Real"
type: adr
tags:
  - adr
  - decision
  - laravel
  - reverb
  - websockets
  - architecture
status: accepted
date: 2026-08-29
---

# ADR-001: Adopción de Laravel 11.x y Laravel Reverb para Telemetría en Tiempo Real

## Estado
**Aceptado** (2026-08-29)

---

## Contexto y Planteamiento del Problema
El sistema **Ambu-U** requiere monitorear ambulancias en ruta con actualizaciones de geolocalización de alta frecuencia (1 a 5 segundos por móvil) y difundir estas coordenadas a los despachadores y salas de urgencias con latencia sub-segundo.

Tradicionalmente, en el ecosistema PHP/Laravel, la comunicación bidireccional en tiempo real se resolvía mediante:
1. Servicios de terceros SaaS (Pusher, Ably) con altos costos recurrentes por mensajes y conexiones concurrentes concurrentes.
2. Servidores Node.js externos (Socket.io) o servidores en Go, lo que introducía duplicidad de stacks, complejidad operativa y desacoplamiento de la autenticación de Laravel.
3. Servidores basados en Swoole/RoadRunner, requiriendo adaptaciones complejas de ciclo de vida en memoria.

---

## Decisión
Se decide adoptar **Laravel 11.x** junto con el servidor de WebSockets oficial de primera clase **Laravel Reverb**.

1. **Reverb como Servidor de WebSockets Nativo**:
   - Corre sobre PHP CLI utilizando ReactPHP/libuv en un proceso dedicado (`php artisan reverb:start`).
   - Implementa el protocolo de Pusher, lo que permite reutilizar librerías cliente maduras (`laravel-echo`, `pusher-js`).
   - Integración nativa con el sistema de autenticación de canales (`routes/channels.php`) y eventos de Laravel (`ShouldBroadcast`, `ShouldBroadcastNow`).

2. **Laravel 11.x como Core**:
   - Estructura de directorios simplificada y moderna (configuraciones unificadas en `bootstrap/app.php` y `routes/api.php` mediante `install:api`).
   - Tipado estricto de PHP 8.2+ (Readonly properties, Enums, DTOs).

---

## Consecuencias

### Positivas
- **Cero costos de licenciamiento de terceros**: Servidor 100% self-hosted escalable vertical y horizontalmente (soporta Redis horizontal scaling).
- **Consistencia en un único lenguaje y ecosistema**: Todo el equipo de ingeniería trabaja en PHP 8.2+ / Laravel sin necesidad de microservicios en Node.js para WebSockets.
- **Rendimiento optimizado**: Capacidad de manejar miles de conexiones WebSocket concurrentes con bajo consumo de memoria.
- **Autenticación sin fricción**: Los canales privados (`private-remission.{id}`) validan el token de Laravel Sanctum de forma directa y nativa.

### Negativas / Trade-offs
- **Proceso Daemon adicional**: Requiere supervisión de proceso en producción mediante `Supervisor` o contenedores Docker (`php artisan reverb:start`).
- **Gestión de Memoria y Conexiones**: Monitoreo de límites de descriptores de archivos (`ulimit -n`) y configuración de proxies inversos (Nginx/Caddy con soporte WebSocket `Upgrade`).

---

## Alternativas Evaluadas

| Alternativa | Ventajas | Desventajas | Razón de Rechazo |
| :--- | :--- | :--- | :--- |
| **Pusher SaaS** | Cero mantenimiento de servidor | Costoso a escala, dependencia de terceros y datos médicos transitando por servidores externos | Costos y soberanía de datos |
| **Node.js + Socket.io** | Ecosistema WebSocket masivo | Duplicidad de stack, necesidad de re-autenticar tokens contra Laravel vía HTTP | Complejidad arquitectónica y sobrecarga de mantenimiento |
| **Swoole / Octane** | Alto throughput | Mayor complejidad de compatibilidad con paquetes tradicionales de Laravel | Reverb resuelve el WebSocket manteniendo el backend HTTP estándar |

---

## 🔗 Referencias Cruzadas

- [[Architecture-MOC]]: Diagrama de integración de Reverb en el sistema.
- [[Telemetry-Haversine]]: Módulo de telemetría que dispara los eventos a Reverb.
- [[API-Contracts]]: Especificación de payloads y canales WebSocket.
