# Comanda Technical Documentation

This repository contains **Comanda**, a full restaurant management platform written in PHP 8. It centralises reservations, waiter assignment, orders, customer-facing flows, inventory and analytics in a monolithic MVC-style project. The goal of this document is to provide a self-contained and technically rich description that another AI (or engineer) can use to reason about, extend or refactor the codebase without additional context.

---

## 1. Domain Overview
- **Personas**: Administradores (back-office), Mozos (floor staff), Clientes (self-service portal).
- **Key Operations**: Table assignment, waiter routing, order lifecycle, waiter calls, tipping, performance dashboards, inventory and revenue reporting.
- **Execution model**: Traditional PHP request/response. `public/index.php` boots the app, inspects `$_GET['route']` and dispatches to a controller or view.
- **Data store**: MySQL 8+ with foreign keys, triggers and summary views.

---

## 2. High-Level Architecture
```
Browser ?? public/index.php (router) ?? Controllers (App\Controllers)
                                          ?
                                          ??? Models (App\Models) ?? Database (PDO)
                                          ?
                                          ??? Views (src/views) ?? HTML/CSS/JS (public/assets)
```
- **Autoloading**: PSR-4 via Composer (`App\` namespace maps to `src/`).
- **Configuration**: `src/config/Database.php`, `CsrfToken.php`, `Validator.php`, `helpers.php`.
- **Routing helpers**: `requireAuth()`, `requireAdmin()`, `requireMozoOrAdmin()` defined in `public/index.php`.
- **Session handling**: Lazy `session_start()` guards present across controllers and views.

---

## 3. Directory Map
```
Comanda/
  public/                 Entry point, .htaccess, static assets
  src/
    config/               Database wrapper, helper functions, CSRF + Validator
    controllers/          Auth, Mesa, Mozo, Carta, Cliente, Pedido, Reporte (admin endpoints)
    models/               Data access objects (Usuario, Mesa, Pedido, etc.)
    views/                PHP templates grouped by module (mesas/, mozos/, pedidos/, cliente/, reportes/?)
  database/               schema.sql, add_status_to_mesas.sql, simple_test_data.sql (seed)
  vendor/                 Composer autoloader (no third-party deps bundled)
  .vscode/                Launch configuration for local debugging
  README.md               (this file)
```

---

## 4. Core Components
### 4.1 Configuration (`src/config`)
- `Database.php`: PDO factory with retry logic, friendly error mapping and non-persistent connections.
- `CsrfToken.php`: Generates/validates per-request CSRF tokens, exposes `CsrfToken::field()` for forms.
- `Validator.php`: Centralised validators (email, enums, numeric ranges, price, passwords) plus sanitisation helpers.
- `helpers.php`: URL helpers (`getBaseUrl()`, `url()`), used by controllers, views and AJAX endpoints.

### 4.2 Controllers (`src/controllers`)
Each controller defines static methods invoked by the router or directly from views.
- `AuthController`: Login/logout with CSRF validation, email sanitisation and role checks.
- `MesaController`: Soft-delete/reactivate tables, redirect with status codes and query-string feedback.
- `MozoController`: CRUD for waiters, intelligent reassignment workflow when deactivating staff, JSON endpoints for waiter calls.
- `CartaController`: CRUD for menu items including URL validation for images and availability toggles.
- `PedidoController`: AJAX endpoints for state transitions, soft-delete, JSON info payloads; used by admin/mozo dashboards.
- `ClienteController`: Customer menu, payment flow (tips, payment method), QR entry point, JSON API for public order creation.
- `ReporteController`: (Removed during cleanup) views now load directly while models/summaries stay active.

### 4.3 Models (`src/models`)
Stateless classes performing SQL queries via PDO. Highlights:
- `Usuario.php`: Fetch users by role/email, uniqueness checks, hashed password management.
- `Mesa.php`: Handles table lifecycle, waiter assignment counts, soft-delete/reactivation, status checks.
- `Pedido.php`: Creates and updates orders with transactional integrity, calculates totals, fetches detail rows and reporting joins.
- `DetallePedido.php`: Inserts order line items, resolves current pricing when not provided, exposes `getByPedido()` used by customer payment view.
- `CartaItem.php`: CRUD for menu entries, cascade delete ensuring dependent order rows removed transactionally.
- `Inventario.php`: Rich inventory API (threshold monitoring, category summaries, stored procedure integration).
- `Propina.php`: Persists tips, aggregate statistics for waiter performance.
- `Reporte.php`: Aggregates data for dashboards (sales per category, best sellers, waiter KPIs) via windowed SQL.
- `LlamadoMesa.php`: Waiter-call management, role-filtered queries, automatic cleanup of stale calls.

### 4.4 Views (`src/views`)
Server-rendered pages with HTML/CSS/JS. They frequently import the Composer autoloader and `helpers.php`. Examples:
- `views/mesas/index.php`: Guarded by role, enforces no-delete rules for occupied tables.
- `views/pedidos/create.php`: Combined create/edit form with client-side validations and server-side guardrails.
- `views/cliente/*.php`: Customer journey (menu, cart, payment confirmation) using JS for dynamic behaviour.
- Shared layout partials in `views/includes/{header,footer,nav}.php` pull CSS/JS from `public/assets`.

---

## 5. Routing Overview (`public/index.php`)
- **Authentication**: `route=login`, POST handled by `AuthController::login()`, CSRF enforced, redirects to `route=home` on success.
- **Dashboard/Home**: `route=home`, requires authenticated user.
- **Mesas**: list/create/edit forms in `src/views/mesas/`, destructive actions delegated to `MesaController::delete/reactivate` (admin only).
- **Mozos**: `route=mozos`, plus create/edit/confirm flows, with AJAX endpoints for reassignment and deletions.
- **Pedidos**: `route=pedidos`, plus `create`, `edit`, `delete`, `info`, `update-estado` (JSON), `cliente-pedido` (public JSON API).
- **Llamados**: `route=llamados`, filtered per waiter; `route=llamar-mozo` public JSON endpoint.
- **Reportes**: `route=reportes` and subroutes render PHP views that query `App\Models\Reporte` directly.
- **Customer routes**: `route=cliente`, `cliente/pago`, `cliente/confirmacion`, `admin/qr-offline` for offline QR generation.

The router includes header/footer automatically for non-AJAX views, ensuring consistent layout.

---

## 6. Database Model (MySQL)
Core tables (partial list relevant to code):
- `usuarios`: Stores administrators and waiters; fields include `rol`, `estado`, hashed `contrasenia`.
- `mesas`: Unique table number, `estado`, `status` (soft-delete switch), `id_mozo`, location metadata.
- `pedidos`: References `mesas` and `usuarios`, tracks `estado`, `modo_consumo`, totals, customer data.
- `detalle_pedido`: Individual line items (`cantidad`, `precio_unitario`, `detalle` note). Triggers deduct inventory in `schema.sql`.
- `carta`: Menu catalogue with `categoria`, availability flag, optional image URL and discount.
- `inventario` + `inventario_movimientos`: Stock tracking with stored procedures for adjustments.
- `llamados_mesa`: Waiter calls with state machine and FK back to `mesas`.
- `propinas`: Optional tip entries linked to `pedidos` and `usuarios`.
- Reporting views/triggers: e.g. `vista_stock_bajo`, `vista_inventario_categoria`, triggers for automatic updates.

Refer to `database/schema.sql` for the definitive definition, plus `simple_test_data.sql` for seeds.

---

## 7. Business Rules & Workflows
1. **Mesa lifecycle**
   - Tables must be unique per number; duplicates rejected at DB and PHP level.
   - Cannot delete/reactivate without admin role.
   - `Mesa::delete()` flips `status` to 0 (soft-delete) and validates existence.
   - Occupied tables (`estado != 'libre'`) cannot be deleted; `Mesa::tienePedidosActivos()` prevents it.

2. **Waiter reassignment**
   - Inactivating a waiter triggers a confirmation flow listing assigned mesas.
   - Admin chooses to reassign to another waiter or free the mesas; controller orchestrates updates.

3. **Order pipeline**
   - Creation populates `detalle_pedido` in a transaction; totals recomputed afterwards.
   - State transitions allowed via `PedidoController::updateEstado()` with guardrails for closed orders.
   - Customer-facing API `ClienteController::crearPedido()` validates payload (name, email, items) and inherits mesa/mozo metadata.

4. **Waiter calls**
   - Public endpoint `MozoController::llamarMozo()` accepts JSON, rate-limits repeated calls within 3 minutes, deletes stale entries >20 minutes.
   - Waiters see only calls for mesas assigned to them; admins see all.

5. **Reports**
   - `Reporte::platosMasVendidos()`, `ventasPorCategoria()`, `rendimientoMozos()` use time-window filters based on `periodo` argument.
   - Tip aggregation, order counts and per-waiter revenue power the admin dashboards.

6. **Security**
   - Unelevated users hitting admin routes are redirected to `route=unauthorized`.
   - Sessions always checked before side-effect actions.
   - `CsrfToken::field()` embedded in forms requiring POST.
   - Inputs sanitised via `Validator::validateMultiple()` patterns and `htmlspecialchars` in views.

---

## 8. Frontend Notes
- **Styling**: Vanilla CSS under `public/assets/css/` (`style.css`, `login.css`, `modal-confirmacion.css`). Theming is consistent (earth tones) with responsive layouts.
- **Scripts**: `modal-confirmacion.js` handles confirmation dialogues. Views include inline JS for form behaviour (e.g., show/hide password, filter tables, manage AJAX requests).
- **Login view**: `views/auth/login.php` uses Google Fonts + Font Awesome, handles CSRF and inline validation.
- **Customer menu**: Uses JS to manage cart state, send JSON orders to the public API.

---

## 9. Environment Setup
1. **Requirements**: PHP 8.x with PDO MySQL extension, Composer, MySQL 8.x, web server (Apache/nginx) or `php -S`.
2. **Install**: `composer dump-autoload` (no external packages required, but regenerates autoloader).
3. **Database**:
   - Create a schema (default credentials in `Database.php` assume `comanda`, user `root`, empty password).
   - Run `database/schema.sql`.
   - Optionally run `database/simple_test_data.sql` for sample users (administrador/mozo) and seed data.
4. **Serve**: Point your server to `public/`. Example: `php -S 127.0.0.1:8000 -t public`.
5. **Credentials**: Use seeded admin/mozo records or insert manually.

---

## 10. Testing & Verification
No automated tests exist. Manual smoke tests are recommended after modifications:
- **Authentication**: Login as admin/mozo, verify redirects and CSRF handling.
- **Mesa workflow**: Create, edit, soft-delete and reactivate a table; ensure orders prevent deletion.
- **Pedido pipeline**: Create order via admin UI, change states via AJAX, close order and confirm mesa freed.
- **Customer API**: Post JSON to `index.php?route=cliente-pedido`, confirm order recorded and totals set.
- **Waiter call**: Trigger `llamar-mozo`, ensure only assigned waiter sees it and rate limiting works.
- **Reports**: Load each report view and validate aggregated numbers against sample data.

---

## 11. Troubleshooting
| Symptom | Likely Cause | Fix |
|--------|--------------|-----|
| `Cannot redeclare...` in models | Duplicate function definitions (previously in `DetallePedido`) | Use single `create()` signature (already fixed). |
| Blank page after login | Database connection failure | Update credentials in `Database.php` or start MySQL. |
| CSRF error on login | Missing/expired token | Ensure form includes `<?= CsrfToken::field() ?>` and session persists. |
| Report pages 404 | Route missing in `public/index.php` | Add case and corresponding view include. |
| `vendor/autoload.php` missing | Autoloader not generated | Run `composer dump-autoload`. |

---

## 12. Maintenance Tips
- **Coding style**: Stateless classes with static methods; new modules should follow `App\Namespace` convention and register via Composer if needed.
- **Transactions**: Follow `Pedido::create()` pattern when operations span multiple tables.
- **Error messaging**: Surface user-friendly errors via query-string parameters (`?error=...`) to keep UX consistent.
- **Deployment**: Lock down `public/`, keep `src/` non-public; configure Apache/Nginx accordingly.
- **Backups**: Use `backup_before_schema_update.sql` as reference when applying migrations.

---

## 13. Recent Cleanup (2025-09)
- Removed legacy artefacts (`Artefactos/`, debug scripts, stray shell outputs).
- Consolidated documentation into this single README file.
- Simplified controllers to only expose active actions; resolved method duplication in `DetallePedido`.

This README is now the authoritative knowledge base for the project. Treat it as the single source of truth for onboarding humans or machine agents.
