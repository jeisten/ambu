---
title: "Módulo de Autenticación y Gestión de Usuarios"
type: module
tags:
  - module
  - auth
  - sanctum
  - security
  - users
status: in-progress
date: 2026-08-29
---

# 🔐 Auth Module: Autenticación y Usuarios

Este módulo gestiona el control de acceso a la API RESTful de **Ambu-U**, la emisión y revocación de tokens personales con **Laravel Sanctum**, y la administración de perfiles de conductores y administradores.

---

## 🎯 Responsabilidades del Módulo
1. Autenticación de conductores en la app móvil mediante correo y contraseña.
2. Emisión de Bearer Tokens persistidos en la tabla `personal_access_tokens` de Sanctum.
3. Diferenciación de roles de usuario (`driver` vs `admin`).
4. Almacenamiento seguro de datos de identificación (`id_number`) y tipificación médica (`blood_type`).

---

## 📐 Diseño de Clases y Estructura

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── AuthController.php
│   ├── Requests/
│   │   └── LoginRequest.php
│   └── Resources/
│       └── UserResource.php
├── Models/
│   └── User.php
```

---

## 🔌 Endpoints Expuestos

| Método | Ruta | Middleware | Descripción |
| :--- | :--- | :--- | :--- |
| `POST` | `/api/login` | `guest` | Autentica credenciales y genera token de Sanctum |
| `POST` | `/api/logout` | `auth:sanctum` | Revoca el token actual del usuario |
| `GET` | `/api/user` | `auth:sanctum` | Retorna el perfil y datos del usuario autenticado |

---

## 🔒 Reglas de Seguridad y Validación
- **Hashing de Contraseñas:** Uso de `Hash::make()` con el algoritmo por defecto de Laravel (Bcrypt / Argon2id).
- **Control de Intentos Fallidos (Rate Limiting):** Aplicar middleware `throttle:6,1` (6 intentos por minuto) en `/api/login` para mitigar ataques de fuerza bruta.
- **Revocación Precisa de Tokens:** En `logout`, revocar únicamente `$request->user()->currentAccessToken()->delete()`.

---

## 🔗 Referencias Cruzadas
- [[API-Contracts]]: Payloads de solicitud y respuesta para autenticación.
- [[Data-Dictionary]]: Estructura de la tabla `users`.
- [[Business-Rules]]: Reglas de negocio de usuarios (`RN-AUT-01` a `RN-AUT-04`).
