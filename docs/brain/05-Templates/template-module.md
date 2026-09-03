---
title: "Módulo: [Nombre del Módulo]"
type: module
tags:
  - module
  - [tag-del-modulo]
status: planned # planned | in-progress | completed | deprecated
date: YYYY-MM-DD
---

# 📦 [Nombre del Módulo]

[Descripción de alto nivel del propósito del módulo dentro de la arquitectura de Ambu-U.]

---

## 🎯 Responsabilidades del Módulo
1. [Responsabilidad 1]
2. [Responsabilidad 2]
3. [Responsabilidad 3]

---

## 📐 Diseño de Clases y Estructura
```
app/
├── Http/
│   ├── Controllers/Api/
│   └── Requests/
├── Models/
└── Services/
```

---

## 🔌 Endpoints Expuestos

| Método | Ruta | Middleware | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/...` | `auth:sanctum` | ... |
| `POST` | `/api/...` | `auth:sanctum` | ... |

---

## 🔒 Reglas de Validación y Negocio
- **RN-XXX-01:** [Regla]
- **RN-XXX-02:** [Regla]

---

## 🔗 Referencias Cruzadas
- [[Architecture-MOC]]
- [[Business-Rules]]
- [[Data-Dictionary]]
- [[API-Contracts]]
