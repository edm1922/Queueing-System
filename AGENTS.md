# Queue Management System — Agent Guide

## Tech Stack
- PHP 8.0+ (flat files, no autoloader/PSR-4)
- MySQL 5.7+ / MariaDB 10.3+ via PDO
- Tailwind CSS 2 + Font Awesome 6 (CDN, no build step)
- Vanilla JS (ES5 style, `var` keyword, no modules/bundler)

## Setup
1. Create MySQL database `queuing_system`, import `database/Schema_v2.sql`
2. Edit credentials in `config.php` (default: root/empty @ localhost)
3. Place in XAMPP `htdocs/`, access via http://localhost/queue-management-system/
4. Default login: `admin` / `admin123`
5. No `composer install` or `npm install` — no package manager

## Key Commands
- No test, lint, typecheck, or CI pipeline exists
- Run via XAMPP/WAMP Apache + MySQL

## Architecture
- **Pages** (no routing): `index.php` (admin dashboard), `display.php` (public screen), `kiosk.php` (self-check-in), `login.php`, `settings.php` (admin only), `reports.php`
- **API**: `api/` directory, JSON endpoints, method-gated (POST/GET), PDO prepared statements with transactions where needed
- **Auth**: Bearer token (header/cookie/body), 8-hour sessions, roles: admin/supervisor/staff
- **DB timezone**: `Asia/Manila` (UTC+8) hardcoded in `config.php`

## Queue Numbering
- Prefix `I` (Insurance, Benefits) and `R` (ID Renewal, ATM Claim)
- Auto-increment via `queue_sequences` table, formatted `I001`, `R010`, `R100`

## Counter Status
- 3 states: `Online` / `On Break` / `Offline`
- Going offline triggers automatic redistribution: services + waiting customers reassigned to an online counter
- `api/counter/toggle_status.php` handles redistribution in a DB transaction

## Public Display
- Polls `api/get_display_data.php` every 3s
- Web Speech API (`speechSynthesis`) for spoken number announcements
- Notification sound (chime from mixkit.co) on new calls
- CSS animations: flip, pulse, glow, slide-in

## Database
- `database/Schema_v2.sql` = authoritative schema (drop+recreate)
- No migration system — edit the SQL dump directly
- No ORM — raw PDO with `fetch(PDO::FETCH_ASSOC)`

## Existing Agent Instructions
- `.agents/rules/graphify.md` — consult `graphify-out/` knowledge graph before answering codebase questions; run `graphify update .` after code changes
- `.agents/workflows/graphify.md` — graphify pipeline reference

## Code Conventions
- PHP: `include` (not `require`), procedural with `Database` class in `config.php`, PDO transactions, no type hints
- JS: `var` / `function`, no modules, ES5-compatible, polling via `setInterval`
- SQL: InnoDB, `utf8mb4_general_ci`, `idx_` prefix on indexes
- `.gitignore` excludes: .htaccess, *.log, .gemini/, node_modules/, .vscode/, .idea/
