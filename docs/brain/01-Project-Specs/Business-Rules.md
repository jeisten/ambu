---
title: "Business Rules & Domain Logic"
type: spec
tags:
  - specs
  - business-rules
  - domain
  - validation
date: 2026-08-29
status: approved
---

# 📋 Business Rules & Domain Logic: Ambu-U

Este documento define con rigor formal las reglas del negocio, validaciones y lógica computacional requeridas en el backend de **Ambu-U**.

---

## 1. Reglas de Autenticación y Usuarios

- **RN-AUT-01 (Roles):** El sistema soporta dos roles exclusivos: `driver` (conductor de ambulancia) y `admin` (administrador hospitalario / despachador). Por defecto, los nuevos registros son `driver`.
- **RN-AUT-02 (Cédula Única):** El campo `id_number` debe ser único en la tabla `users` para garantizar correspondencia 1:1 con la identidad civil del conductor.
- **RN-AUT-03 (Tipo de Sangre):** Es obligatorio registrar el grupo sanguíneo del conductor entre los valores válidos: `A+`, `A-`, `B+`, `B-`, `AB+`, `AB-`, `O+`, `O-`.
- **RN-AUT-04 (Token Sanctum):** La app móvil debe incluir el token Bearer en el header `Authorization: Bearer <token>` para todos los endpoints protegidos.

---

## 2. Gestión de Flota y Ambulancias

- **RN-FLT-01 (Identificador Placa):** Cada ambulancia tiene una placa única en formato alfanumérico.
- **RN-FLT-02 (Rendimiento de Combustible):** Cada unidad debe tener parametrizado el factor `km_per_gallon` (número decimal mayor a 0) que representa cuántos kilómetros recorre por cada galón consumido.
- **RN-FLT-03 (Vigencia de Documentación Obligatoria):**
  - Toda ambulancia debe contar con fecha de expedición y vencimiento de **SOAT** (`soat_issue_date`, `soat_expiry_date`).
  - Toda ambulancia debe contar con fecha de expedición y vencimiento de **Revisión Tecnomecánica** (`tecnomecanica_issue_date`, `tecnomecanica_expiry_date`).
- **RN-FLT-04 (Estados de la Ambulancia):**
  - `active`: Disponible u operando normalmente.
  - `maintenance`: En taller o fuera de servicio por reparaciones.
  - `inactive`: Deshabilitada del sistema o con documentos vencidos.
- **RN-FLT-05 (Alerta Proactiva de Vencimiento):**
  - Una tarea programada diaria (`CheckDocuments`) evalúa si `DATEDIFF(soat_expiry_date, NOW()) <= 5` o `DATEDIFF(tecnomecanica_expiry_date, NOW()) <= 5`.
  - Si cumple la condición, se genera una notificación al equipo de soporte/administración.

---

## 3. Gestión de Pacientes

- **RN-PAC-01 (Identificación Paciente):** El paciente se identifica por `id_type` (`CC`, `TI`, `CE`, `RC`, `PAS`) e `id_number` (único).
- **RN-PAC-02 (Régimen de Salud):** Debe pertenecer a uno de los regímenes: `Contributivo`, `Subsidiado`, `Particular`, `Vinculado`.
- **RN-PAC-03 (Caso SOAT):** Si `is_soat_case` es `true`, el campo `soat_company` es obligatorio indicando la aseguradora responsable del siniestro.
- **RN-PAC-04 (Upsert / Find-or-Create):** Al registrar un paciente en la app, si ya existe por `id_number`, se devuelven los datos existentes o se actualiza la información básica sin duplicar registros.

---

## 4. Ciclo de Vida de la Remisión (Traslado)

- **RN-REM-01 (Transiciones de Estado):**
  ```
  [Inicio] --> 'en_camino' (Hacia el punto de recogida)
           --> 'trasladando' (Paciente a bordo, en ruta al hospital)
           --> 'finalizado' (Llegada a destino y entrega de paciente)
  ```
- **RN-REM-02 (Inicio de Remisión):**
  - Requiere obligatoriamente un conductor asignado (`driver_id`), una ambulancia activa (`ambulance_id`) y un paciente (`patient_id`).
  - Se pueden adjuntar de 0 a N ocupantes extra (`remission_occupants`) con roles: `Médico`, `Enfermero`, `Familiar`, `Estudiante`, `Otro`.
  - Se registra el `start_time` con la hora actual UTC/servidor.
  - `total_kilometers` y `fuel_consumed_gallons` inician en `0.00`.
- **RN-REM-03 (Remisión Fuera de Ciudad):** Se marca mediante el booleano `is_out_of_city` para segregación en reportes estadísticos y facturación especial.

---

## 5. Ingesta de Telemetría y Cálculo Haversine

- **RN-TEL-01 (Recepción de Coordenadas):** La app móvil envía paquetes periódicos con `latitude`, `longitude` y opcionalmente `speed`.
- **RN-TEL-02 (Cálculo de Distancia Incremental):**
  - El backend consulta el último registro previo en `locations` para ese `remission_id`.
  - Si existe un punto anterior $(lat_1, lon_1)$, calcula la distancia geodésica $d$ en kilómetros con el punto nuevo $(lat_2, lon_2)$ aplicando la **Fórmula de Haversine**:
    $$a = \sin^2\left(\frac{\Delta \text{lat}}{2}\right) + \cos(\text{lat}_1) \cdot \cos(\text{lat}_2) \cdot \sin^2\left(\frac{\Delta \text{lon}}{2}\right)$$
    $$c = 2 \cdot \text{atan2}\left(\sqrt{a}, \sqrt{1-a}\right)$$
    $$d = R \cdot c \quad \text{(donde } R = 6371 \text{ km)}$$
  - El valor $d$ se suma al campo acumulativo `remissions.total_kilometers`.
- **RN-TEL-03 (Filtro de Ruido GPS):** Si $d < 0.005\text{ km}$ (menos de 5 metros), se puede omitir la suma de kilometraje para evitar fluctuaciones por jitter del GPS estando detenido.
- **RN-TEL-04 (Broadcasting WebSocket):** Cada coordenada válida insertada dispara inmediatamente un evento de difusión `LocationUpdated` a través de **Laravel Reverb**. Ver [[Telemetry-Haversine]].

---

## 6. Cierre de Remisión y Cálculo de Combustible

- **RN-FIN-01 (Cálculo de Consumo):** Al ejecutar el cierre (`PUT /api/remissions/{id}/finish`):
  $$\text{fuel\_consumed\_gallons} = \frac{\text{remissions.total\_kilometers}}{\text{ambulances.km\_per\_gallon}}$$
- **RN-FIN-02 (Estampa Temporal de Fin):** Se asigna `end_time = now()` y el estado pasa a `finalizado`.
- **RN-FIN-03 (Inmutabilidad Post-Cierre):** No se permiten nuevas inserciones de telemetría en remisiones con estado `finalizado`.

---

## 🔗 Referencias Cruzadas

- [[Architecture-MOC]]: Arquitectura y flujo general.
- [[Data-Dictionary]]: Estructura de campos y tablas.
- [[API-Contracts]]: Especificación técnica de endpoints.
- [[Telemetry-Haversine]]: Módulo y fórmula de telemetría.
