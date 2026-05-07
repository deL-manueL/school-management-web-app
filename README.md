# IPMC School Management System

A PHP + MySQL web application for IPMC University College with a student-facing portal and a PHP admin dashboard. The frontend is static HTML/JS deployed on Vercel; the backend runs on Apache (Docker) on Railway.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.2, Apache (`php:8.2-apache`) |
| Database | MySQL 5.7+ via `mysqli` prepared statements |
| Sessions | PHP file-based sessions in `storage/sessions/` |
| Frontend | Plain HTML5, CSS3, Vanilla JS — no framework, no build step |
| Backend deployment | [Railway](https://railway.app) (Docker) |
| Frontend deployment | [Vercel](https://vercel.com) (static) |

---

## Project Structure

```
final-project/
├── admin-dashboard/
│   ├── api/                        # JSON API endpoints
│   │   ├── check_session.php
│   │   ├── contact_form_api.php
│   │   ├── get_contact_courses.php
│   │   ├── get_student_data.php
│   │   ├── get_student_data_admin.php
│   │   ├── get_student_grievances.php
│   │   ├── get_student_results.php
│   │   ├── logout_api.php
│   │   ├── student_login_api.php
│   │   ├── student_register_api.php
│   │   ├── submit_grievance_api.php
│   │   └── upload_result_api.php
│   ├── config.php                  # Central config: DB, sessions, CORS, helpers
│   ├── index.php                   # Admin login
│   ├── dashboard.php
│   ├── students.php
│   ├── courses.php
│   ├── results.php                 # Single + CSV bulk result upload
│   ├── grievances.php
│   ├── contacts.php
│   ├── sidebar.php
│   ├── fix_admin.php               # Admin bootstrap (ALLOW_SETUP_SCRIPTS gate)
│   └── sample_results.csv          # CSV template for bulk upload
├── index.html
├── contact.html
├── grievance.html                  # Student portal (login, results, grievances)
├── frontend-config.js              # Resolves window.IPMC_CONFIG.apiUrl()
├── styles.css
├── database.sql                    # Full schema + seed data
├── Dockerfile                      # php:8.2-apache, mpm_prefork, mod_rewrite
├── docker-entrypoint.sh            # Patches Apache port from $PORT at startup
├── railway.json
├── .env                            # Local secrets (gitignored)
├── .env.example
├── .vercelignore
└── .railwayignore
```

**`config.php`** — single source of truth: loads `.env`, defines `getConnection()`, `startSession()`, `configureCors()`, `apiResponse()`, `requireStudentSession()`, `requireAdminSession()`, and all shared helpers.

**`frontend-config.js`** — resolves the API base URL from (in order): `window.IPMC_API_BASE_URL`, `<meta name="api-base-url">`, `localStorage`, or localhost default. Exposes `window.IPMC_CONFIG.apiUrl(path)`. Throws a hard error in production if no URL is configured.

**`docker-entrypoint.sh`** — runs at container startup: patches `ports.conf` and the default vhost to use Railway's `$PORT`, removes any conflicting MPM modules, then hands off to `apache2-foreground`.

**All API responses** use the shape:
```json
{ "success": true, "message": "...", "data": { ...payload... } }
```
JS code must access payload as `data.data.student`, `data.data.results`, etc. — **not** `data.student`.

---

## Local Setup

**Requirements**: XAMPP (PHP 8.2+, MySQL), Git.

```bash
# 1. Place the project
git clone <repo-url> "C:\xampp\htdocs\final-project"

# 2. Start XAMPP — Apache + MySQL

# 3. Create the database
"C:\xampp\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE school_system CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"

# 4. Import the schema
"C:\xampp\mysql\bin\mysql.exe" -u root school_system < "C:\xampp\htdocs\final-project\database.sql"

# 5. Configure environment
copy .env.example .env
# Edit .env — set DB credentials and ADMIN_EMAIL / ADMIN_PASSWORD
```

**Minimum `.env`:**
```ini
APP_ENV=local
DB_HOST=127.0.0.1
DB_PORT=3306
DB_USER=root
DB_PASS=
DB_NAME=school_system
FRONTEND_ORIGIN=http://localhost,http://127.0.0.1
SESSION_SECURE=false
SESSION_SAMESITE=Lax
SESSION_SAVE_PATH=storage/sessions
ALLOW_SETUP_SCRIPTS=false
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=change-me
```

**Create the admin user:**

1. Set `ALLOW_SETUP_SCRIPTS=true` in `.env`
2. Visit `http://localhost/final-project/admin-dashboard/fix_admin.php`
3. Confirm success, then set `ALLOW_SETUP_SCRIPTS=false`

**Access:**

| URL | Purpose |
|---|---|
| `http://localhost/final-project/` | Public site |
| `http://localhost/final-project/grievance.html` | Student portal |
| `http://localhost/final-project/admin-dashboard/` | Admin login |

---

## Environment Variables

### Local (`.env`)

| Variable | Description |
|---|---|
| `APP_ENV` | `local` or `production`. Controls `display_errors`. |
| `DB_HOST` / `DB_PORT` / `DB_USER` / `DB_PASS` / `DB_NAME` | MySQL connection |
| `FRONTEND_ORIGIN` | Comma-separated allowed CORS origins |
| `SESSION_SECURE` | `false` for HTTP (local), `true` for HTTPS (prod) |
| `SESSION_SAMESITE` | `Lax` (same-domain) or `None` (cross-domain HTTPS) |
| `SESSION_SAVE_PATH` | Path for PHP session files |
| `ALLOW_SETUP_SCRIPTS` | `true` only during initial admin bootstrap — always `false` in prod |
| `ADMIN_EMAIL` / `ADMIN_PASSWORD` | Admin bootstrap credentials for `fix_admin.php` |

### Production (Railway)

Railway's MySQL plugin auto-injects `MYSQLHOST`, `MYSQLPORT`, `MYSQLUSER`, `MYSQLPASSWORD`, `MYSQLDATABASE`. `config.php` resolves these with fallback to `DB_*` names automatically.

Set these manually on the Railway PHP service:

| Variable | Value |
|---|---|
| `APP_ENV` | `production` |
| `FRONTEND_ORIGIN` | `https://your-app.vercel.app` (no trailing slash) |
| `SESSION_SECURE` | `true` |
| `SESSION_SAMESITE` | `None` |
| `ALLOW_SETUP_SCRIPTS` | `false` |
| `ADMIN_EMAIL` / `ADMIN_PASSWORD` | Production admin credentials |

---

## API Reference

All endpoints are under `admin-dashboard/api/`. All responses: `{ success, message, data }`.

| Method | Endpoint | Auth | Purpose |
|---|---|---|---|
| `POST` | `contact_form_api.php` | None | Save contact form submission |
| `POST` | `student_register_api.php` | None | Register student, returns auto-generated `student_id` |
| `POST` | `student_login_api.php` | None | Authenticate student, set session cookie |
| `GET` | `check_session.php` | None | Check if a student session is active |
| `POST` | `logout_api.php` | None | Destroy session |
| `GET` | `get_student_data.php` | Student session | Authenticated student's profile + results |
| `GET` | `get_student_results.php` | Student session | Authenticated student's results + GPA |
| `GET` | `get_student_grievances.php` | Student session | Authenticated student's grievances |
| `POST` | `submit_grievance_api.php` | Student session | Submit a new grievance |
| `GET` | `get_contact_courses.php` | None | Courses for contact form dropdown |
| `POST` | `upload_result_api.php` | Admin session | Upload or update a single student result |
| `GET` | `get_student_data_admin.php` | Admin session | Any student's data by `?student_id=` (admin only) |

Student identity on all student-auth endpoints comes **exclusively from the session** — URL/body `student_id` parameters are ignored to prevent horizontal privilege escalation.

---

## Deployment

### Backend — Railway (Docker)

1. **Create Railway project** → Deploy from GitHub repo (branch `main`). Railway detects `Dockerfile` and builds automatically.

2. **Add MySQL** → inside the project, **+ New → Database → MySQL**. Railway injects connection variables automatically.

3. **Set environment variables** (see table above).

4. **Import the database schema:**

   ```bash
   mysql -h <MYSQLHOST> -P <MYSQLPORT> -u <MYSQLUSER> -p<MYSQLPASSWORD> <MYSQLDATABASE> < database.sql
   ```

5. **Bootstrap the admin user:**
   - Set `ALLOW_SETUP_SCRIPTS=true` → Railway redeploys
   - Visit `https://your-app.up.railway.app/admin-dashboard/fix_admin.php`
   - Confirm success → revert `ALLOW_SETUP_SCRIPTS=false`

6. **Verify:**

   ```bash
   curl https://your-app.up.railway.app/admin-dashboard/api/check_session.php
   # → {"success":false,"message":"Not logged in","data":{"logged_in":false}}

   curl -I https://your-app.up.railway.app/admin-dashboard/fix_admin.php
   # → HTTP/2 403
   ```

### Frontend — Vercel

1. **Set the API base URL** in `<head>` of `contact.html` and `grievance.html`, before `frontend-config.js`:

   ```html
   <meta name="api-base-url" content="https://your-app.up.railway.app/admin-dashboard/api/">
   ```

2. **Deploy** → Vercel Dashboard → New Project → Import repo. Framework: Other. Build command: empty. Output directory: `.`.

3. **Verify** the course dropdown on `contact.html` and student login on `grievance.html` both reach the Railway domain (check Network tab — no requests to `vercel.app`).

---

## Common Issues

**Student login works locally but not on Vercel**
Cross-domain cookies require `SESSION_SECURE=true` and `SESSION_SAMESITE=None` on Railway. `FRONTEND_ORIGIN` must exactly match the Vercel URL (no trailing slash).

**"IPMC_CONFIG is not defined" / broken contact form**
The `<meta name="api-base-url">` tag is missing. Add it to `<head>` before `frontend-config.js`.

**Apache fails to start — `AH00534: More than one MPM loaded`**
`docker-entrypoint.sh` removes conflicting MPM modules at startup. If this error appears in Railway logs, confirm the entrypoint is running (check for `MPM OK:` in the log output).

**502 Bad Gateway on Railway**
Apache is not listening on Railway's injected `$PORT`. Confirm `docker-entrypoint.sh` outputs `Apache will listen on port <PORT>` and that `ports.conf` was patched successfully.

**Admin login fails after setup**
Re-run `fix_admin.php` with `ALLOW_SETUP_SCRIPTS=true`. Confirm the "Password verification test: PASSED" line appears before reverting the flag.

**"Permission denied" writing session files**
`startSession()` creates `storage/sessions/` automatically. If it still fails, set `SESSION_SAVE_PATH=` (empty) to fall back to PHP's default session directory.

---

## Security

- **SQL injection**: all queries use MySQLi prepared statements. Filter values (category, status) are validated against hardcoded allowlists before use.
- **XSS**: all user data rendered in admin HTML is passed through `htmlspecialchars()`.
- **DB errors**: `$conn->error` is written to `error_log()` only — never echoed to the browser.
- **Passwords**: bcrypt via `password_hash($plain, PASSWORD_DEFAULT)` for all users.
- **Session**: `HttpOnly`, configurable `Secure` + `SameSite`. Student identity read from session only.
- **Setup scripts**: gated by `ALLOW_SETUP_SCRIPTS` env var and excluded from Railway via `.railwayignore`.
- **Secrets**: no credentials in source. `.env` is gitignored and excluded from both Vercel and Railway deployments.

---

## License

Developed as a final year academic project for IPMC University College.
