# GOSTUDY — Backend (Laravel 12)

API REST del Sistema de Matrícula Digital del Colegio Frilix.

## Stack
- Laravel 12 (PHP 8.2)
- MySQL 8 / MariaDB
- Sanctum (**Bearer tokens, NO statefulApi**)
- Pest (testing)

## Setup local

```bash
git clone git@github.com:DBLTECH2026/GOSTUDY-BACKEND.git
cd GOSTUDY-BACKEND
composer install
cp .env.example .env
php artisan key:generate

# Crear BD vacía en MySQL llamada "gostudy"
php artisan migrate --seed
php artisan serve
```

API disponible en: `http://localhost:8000/api/v1`

## Estructura modular

```
app/Modules/
├── Auth/         ← Persona A
├── Personas/     ← Persona A
├── Catalogos/    ← Persona B
├── Matricula/    ← Persona B
├── Pagos/        ← Persona C
└── Reportes/     ← Persona C

routes/
├── api.php             ← NO TOCAR (solo incluye los demás)
└── api/
    ├── auth.php        ← Persona A
    ├── personas.php    ← Persona A
    ├── catalogos.php   ← Persona B
    ├── matricula.php   ← Persona B
    ├── pagos.php       ← Persona C
    └── reportes.php    ← Persona C
```

## Reglas anti-conflicto (lee antes de codear)

1. **No tocar `routes/api.php`** — solo edita el archivo de tu módulo en `routes/api/`.
2. **Migraciones con prefijo asignado:**
   - Persona A: `2026_01_*`
   - Persona B: `2026_02_*`
   - Persona C: `2026_03_*`
3. **Modelos comunes** van en `app/Models/`. Solo el dueño los modifica; los demás los leen.
4. **Antes de instalar dep nueva** (`composer require`), avisa al grupo.
5. **Nunca push directo a `main` ni `develop`.** PR siempre.

## Branching

```
main          ← protegida
└── develop   ← integración
    ├── feat/A-auth
    ├── feat/A-personas
    ├── feat/B-catalogos
    ├── feat/B-matricula
    ├── feat/C-pagos
    └── feat/C-reportes
```

## Convenciones

- Validación con `FormRequest` (NUNCA en controller)
- Respuestas con `API Resource`
- `softDeletes` en personas y matrículas
- Tests Pest mínimo de happy path por endpoint
- Commits en español: `feat(auth): agregar endpoint /me`

## Documentación adicional

Ver carpeta `../docs/` en el repositorio raíz:
- `00_PLANIFICACION_EQUIPO.md` — Plan completo del proyecto
- `01_MODELO_DATOS.md` — Schema BD detallado
- `02_API_CONTRATO.md` — Endpoints REST
