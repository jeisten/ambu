---
title: "Módulo de Gestión de Flota de Ambulancias"
type: module
tags:
  - module
  - fleet
  - ambulances
  - maintenance
  - scheduling
status: planned
date: 2026-08-29
---

# 🚑 Fleet & Ambulance Management Module

Este módulo es responsable del inventario de vehículos de emergencia (ambulancias), el control de sus métricas de rendimiento por combustible, la supervisión de la vigencia de documentos legales y las alertas preventivas de vencimiento.

---

## 🎯 Responsabilidades del Módulo
1. Mantenimiento del catálogo de ambulancias y estado operativo (`active`, `maintenance`, `inactive`).
2. Configuración de rendimiento de combustible en `km_per_gallon`.
3. Seguimiento de fechas límite de **SOAT** y **Revisión Tecnomecánica**.
4. Ejecución diaria de la tarea programada `CheckDocuments` para emitir alertas antes del vencimiento.

---

## 📐 Diseño de Clases y Estructura

```
app/
├── Console/
│   └── Commands/
│       └── CheckDocuments.php
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── AmbulanceController.php
│   └── Resources/
│       └── AmbulanceResource.php
├── Models/
│   └── Ambulance.php
└── Notifications/
    └── DocumentExpiringNotification.php
```

---

## ⏰ Tarea Programada (Task Scheduling)

El comando `CheckDocuments` consulta las ambulancias cuyo SOAT o Tecnomecánica vencerán en los próximos 5 días:

```php
// app/Console/Commands/CheckDocuments.php
$expiringAmbulances = Ambulance::query()
    ->where('status', 'active')
    ->where(function ($query) {
        $query->whereRaw('DATEDIFF(soat_expiry_date, CURDATE()) BETWEEN 0 AND 5')
              ->orWhereRaw('DATEDIFF(tecnomecanica_expiry_date, CURDATE()) BETWEEN 0 AND 5');
    })
    ->get();
```

Registrado en `routes/console.php`:
```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('app:check-documents')->daily();
```

---

## 🔗 Referencias Cruzadas
- [[Data-Dictionary]]: Estructura de la tabla `ambulances`.
- [[Business-Rules]]: Reglas de flota (`RN-FLT-01` a `RN-FLT-05`).
- [[API-Contracts]]: Endpoints de ambulancias y estadísticas.
