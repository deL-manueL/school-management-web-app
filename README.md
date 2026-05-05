# IPMC School Management System

A full-stack web application for IPMC University College that provides a student-facing portal for academic results and grievance management, paired with a PHP admin dashboard for course, student, and result administration.

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [Tech Stack](#2-tech-stack)
3. [Project Structure](#3-project-structure)
4. [Local Setup](#4-local-setup)
5. [Environment Variables](#5-environment-variables)
6. [Authentication & Security Model](#6-authentication--security-model)
7. [API Reference](#7-api-reference)
8. [Deployment](#8-deployment)
9. [Post-Deployment Verification](#9-post-deployment-verification)
10. [Common Issues & Fixes](#10-common-issues--fixes)
11. [Security Considerations](#11-security-considerations)
12. [Limitations & Future Improvements](#12-limitations--future-improvements)

---

## 1. Project Overview

This system serves two audiences:

**Students** — Register and log in to view their academic results, cumulative GPA, and submit or track academic grievances through a browser-based portal (`grievance.html`).

**Administrators** — Log in to a PHP admin dashboard to manage students, upload individual or bulk (CSV) academic results, manage course listings, respond to grievances, and view contact form submissions.

### Key Features

- **Student portal** — session-authenticated login, registration, result viewer with GPA calculation, grievance submission and status tracking
- **Admin dashboard** — full CRUD for courses, result upload (single and CSV bulk), student management, grievance reply workflow, contact message inbox
- **REST API layer** — JSON-only API endpoints for all student-facing operations; admin operations handled through PHP form submissions and one admin-specific API endpoint
- **Static frontend** — all public-facing pages are plain HTML/CSS/JS with no build step, deployable to any static host (Vercel)
- **Separated deployment** — static frontend on Vercel, PHP backend + MySQL on Railway, CORS-controlled communication between them

### Architecture

```
┌────────────────────────────────┐     HTTPS + CORS      ┌─────────────────────────────┐
│   Vercel (Static Frontend)     │ ────────────────────▶  │   Railway (PHP Backend)     │
│                                │                        │                             │
│  index.html                    │                        │  admin-dashboard/api/*.php  │
│  contact.html                  │                        │  admin-dashboard/*.php      │
│  grievance.html                │                        │  config.php                 │
│  frontend-config.js            │                        │                             │
│  portal.js / script.js         │                        │   ┌─────────────────────┐   │
└────────────────────────────────┘                        │   │  Railway MySQL DB   │   │
                                                          │   └─────────────────────┘   │
                                                          └─────────────────────────────┘
```

The PHP built-in server (`php -S`) serves both the admin dashboard HTML pages and the API from a single Railway service. The static frontend is deployed independently to Vercel and points to the Railway service via a configured API base URL.

---

## 2. Tech Stack

| Layer | Technology |
|---|---|
| Backend language | PHP 7.4+ (uses `password_hash`, `PASSWORD_DEFAULT`, `mysqli`, `json_encode`) |
| Backend server | PHP built-in server (`php -S`) on Railway; Apache via XAMPP locally |
| Database | MySQL 5.7+ / MariaDB 10.3+ |
| Database extension | `mysqli` (procedural prepared statements via OOP interface) |
| Session storage | PHP file-based sessions in `storage/sessions/` |
| Frontend | Plain HTML5, CSS3, Vanilla JavaScript (no framework, no bundler) |
| CSS utility | Tailwind CSS (via CDN, `tailwind.config.js` present for optional local build) |
| Icons | Font Awesome 6 (CDN) |
| Backend deployment | [Railway](https://railway.app) |
| Frontend deployment | [Vercel](https://vercel.com) |
| Environment config | `.env` file parsed by a custom `loadEnvFile()` function in `config.php` |

**No Composer, no npm runtime dependency, no framework.** The backend is plain PHP with zero third-party packages.

---

## 3. Project Structure

```
final-project/
├── admin-dashboard/              # Entire PHP backend
│   ├── api/                      # JSON API endpoints
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
│   ├── css/
│   │   └── admin-style.css
│   ├── storage/                  # gitignored; runtime session files
│   ├── config.php                # Central config: DB, sessions, CORS, helpers
│   ├── index.php                 # Admin login page
│   ├── dashboard.php             # Admin dashboard home
│   ├── students.php              # Student list and management
│   ├── courses.php               # Course CRUD
│   ├── results.php               # Result upload (single + CSV)
│   ├── grievances.php            # Grievance management and reply
│   ├── contacts.php              # Contact form submissions inbox
│   ├── sidebar.php               # Shared admin sidebar include
│   ├── fix_admin.php             # Bootstrap admin user from env (ALLOW_SETUP_SCRIPTS)
│   ├── setup_admin.php           # Alternate admin bootstrap (ALLOW_SETUP_SCRIPTS)
│   ├── check_admin.php           # Inspect admin_users table (ALLOW_SETUP_SCRIPTS)
│   ├── check_results.php         # Inspect results table (ALLOW_SETUP_SCRIPTS)
│   ├── debug_all.php             # Full system debug report (ALLOW_SETUP_SCRIPTS)
│   ├── test_connection.php       # DB connection test (ALLOW_SETUP_SCRIPTS)
│   ├── hash.php                  # Password hash utility (ALLOW_SETUP_SCRIPTS)
│   ├── hash_password.php         # Password hash utility (ALLOW_SETUP_SCRIPTS)
│   ├── generate_hash.php         # Password hash utility (ALLOW_SETUP_SCRIPTS)
│   └── sample_results.csv        # Template for bulk result upload
├── images/                       # Static images served by both hosts
├── src/                          # Additional static assets
├── storage/
│   └── sessions/                 # PHP session files (gitignored)
├── index.html                    # Public homepage
├── about.html
├── career.html
├── contact.html                  # Contact form (calls contact_form_api.php)
├── explore.html
├── gallery.html
├── grievance.html                # Student portal (login, results, grievances)
├── frontend-config.js            # Resolves and exposes window.IPMC_CONFIG.apiBaseUrl
├── portal.js                     # UI helper for homepage (slider, gallery, nav)
├── script.js                     # Shared JS utilities
├── styles.css                    # Global stylesheet
├── database.sql                  # Full schema + seed data
├── .env                          # Local secrets (gitignored)
├── .env.example                  # Template for .env
├── .gitignore
├── .vercelignore                 # Excludes backend from Vercel
├── .railwayignore                # Excludes debug/setup scripts from Railway
└── railway.json                  # Railway start command config
```

### Key File Descriptions

**`admin-dashboard/config.php`**
The single source of truth for the backend. It loads `.env`, defines all DB constants, provides `getConnection()`, `startSession()`, `configureCors()`, `apiResponse()`, `requireStudentSession()`, `requireAdminSession()`, `requireAdminLogin()`, `sanitizeInput()`, `calculateGPA()`, and other shared helpers. Every PHP file in the project requires this file.

**`frontend-config.js`**
An immediately-invoked script that resolves the API base URL using this priority order:
1. `window.IPMC_API_BASE_URL` (set inline before the script loads)
2. `<meta name="api-base-url" content="...">` in the page `<head>`
3. `localStorage.getItem('IPMC_API_BASE_URL')`
4. On localhost: defaults to `/admin-dashboard/api/`
5. On any other domain: **throws a hard error** — production must be explicitly configured

The result is exposed as `window.IPMC_CONFIG.apiBaseUrl` and `window.IPMC_CONFIG.apiUrl(path)`.

**`database.sql`**
Creates and populates five tables: `students`, `admin_users`, `courses`, `results`, `grievances`, `contacts`. Import this once on a fresh database.

**`railway.json`**
```json
{
  "$schema": "https://railway.com/railway.schema.json",
  "deploy": {
    "startCommand": "php -S 0.0.0.0:$PORT -t ."
  }
}
```
Starts PHP's built-in server, rooted at the project directory. This serves both the static frontend and the PHP admin dashboard from a single Railway service.

**`storage/sessions/`**
PHP session files are written here by `startSession()`. The path is configurable via `SESSION_SAVE_PATH`. This directory is gitignored; it is created at runtime by `startSession()` if it does not exist.

---

## 4. Local Setup

### Requirements

- [XAMPP](https://www.apachefriends.org/) (includes PHP 7.4+ and MySQL) — or PHP 7.4+ and MySQL installed separately
- PHP must have the `mysqli` extension enabled (enabled by default in XAMPP)
- Git

### Step 1 — Place the Project

Clone or extract the project to:

```
C:\xampp\htdocs\final-project\
```

The folder name must be `final-project` (no spaces). If you use a different name or path, update all references to `localhost/final-project/` accordingly.

```bash
git clone <your-repo-url> "C:\xampp\htdocs\final-project"
```

### Step 2 — Start XAMPP

Open the XAMPP Control Panel and start:
- **Apache**
- **MySQL**

### Step 3 — Create the Database

Open [http://localhost/phpmyadmin](http://localhost/phpmyadmin), then:

1. Click **New** in the left sidebar
2. Enter database name: `school_system`
3. Collation: `utf8mb4_general_ci`
4. Click **Create**

Or run via MySQL CLI:

```bash
"C:\xampp\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE school_system CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
```

### Step 4 — Import the Schema

```bash
"C:\xampp\mysql\bin\mysql.exe" -u root school_system < "C:\xampp\htdocs\final-project\database.sql"
```

Or in phpMyAdmin: select `school_system` → **Import** tab → choose `database.sql` → **Go**.

### Step 5 — Configure `.env`

```bash
cd C:\xampp\htdocs\final-project
copy .env.example .env
```

Edit `.env` with your local values. The minimum required change is `ADMIN_EMAIL` and `ADMIN_PASSWORD`:

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
SESSION_DOMAIN=
SESSION_SAVE_PATH=storage/sessions

ALLOW_SETUP_SCRIPTS=false

ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=your-local-password
```

### Step 6 — Create the Admin User

Enable the setup scripts temporarily, then run the bootstrap:

1. Set `ALLOW_SETUP_SCRIPTS=true` in `.env`
2. Visit: [http://localhost/final-project/admin-dashboard/fix_admin.php](http://localhost/final-project/admin-dashboard/fix_admin.php)
3. Confirm "✅ Admin User Created Successfully!"
4. **Immediately** set `ALLOW_SETUP_SCRIPTS=false` in `.env`

Alternatively, insert directly via SQL:

```bash
# First generate a hash in PHP:
php -r "echo password_hash('your-password', PASSWORD_DEFAULT);"
```

```sql
INSERT INTO admin_users (username, email, password)
VALUES ('admin', 'admin@example.com', '<paste-hash-here>');
```

### Step 7 — Access the Project

| URL | Purpose |
|---|---|
| `http://localhost/final-project/` | Public homepage |
| `http://localhost/final-project/contact.html` | Contact form |
| `http://localhost/final-project/grievance.html` | Student portal |
| `http://localhost/final-project/admin-dashboard/` | Admin login |
| `http://localhost/final-project/admin-dashboard/dashboard.php` | Admin dashboard (after login) |

---

## 5. Environment Variables

### Local Development (`.env`)

| Variable | Description | Example | Required |
|---|---|---|---|
| `APP_ENV` | Application environment. Set to `local` locally, `production` on Railway. Controls `display_errors`. | `local` | Yes |
| `DB_HOST` | MySQL hostname | `127.0.0.1` | Yes |
| `DB_PORT` | MySQL port | `3306` | Yes |
| `DB_USER` | MySQL username | `root` | Yes |
| `DB_PASS` | MySQL password (blank for default XAMPP) | *(empty)* | Yes |
| `DB_NAME` | MySQL database name | `school_system` | Yes |
| `FRONTEND_ORIGIN` | Comma-separated allowed CORS origins | `http://localhost,http://127.0.0.1` | Yes |
| `SESSION_SECURE` | Set cookie `Secure` flag. Must be `false` for plain HTTP. | `false` | Yes |
| `SESSION_SAMESITE` | SameSite cookie policy. `Lax` for same-domain, `None` for cross-domain HTTPS. | `Lax` | Yes |
| `SESSION_DOMAIN` | Cookie domain. Leave blank for default (hostname only). | *(empty)* | No |
| `SESSION_SAVE_PATH` | Directory for PHP session files, relative to `admin-dashboard/..` | `storage/sessions` | No |
| `ALLOW_SETUP_SCRIPTS` | Enables setup/debug PHP scripts. **Always `false` in production.** | `false` | Yes |
| `ADMIN_EMAIL` | Email address for the admin user. Read by `fix_admin.php` and `setup_admin.php`. | `admin@example.com` | Yes |
| `ADMIN_PASSWORD` | Plain-text password for the admin user. Hashed at runtime by setup scripts. | `change-before-use` | Yes |

### Production — Railway

Railway's MySQL plugin automatically injects the following variables. Reference them in your PHP service's variable settings:

| Variable | Source | Description |
|---|---|---|
| `MYSQLHOST` | Auto-injected by Railway | Database hostname |
| `MYSQLPORT` | Auto-injected by Railway | Database port |
| `MYSQLUSER` | Auto-injected by Railway | Database username |
| `MYSQLPASSWORD` | Auto-injected by Railway | Database password |
| `MYSQLDATABASE` | Auto-injected by Railway | Database name |

`config.php` resolves DB credentials using `envValue(['DB_HOST', 'MYSQLHOST'], 'localhost')`, so both naming conventions are supported automatically.

Set these manually in the Railway PHP service:

| Variable | Value | Notes |
|---|---|---|
| `APP_ENV` | `production` | Disables `display_errors` |
| `FRONTEND_ORIGIN` | `https://your-app.vercel.app` | Exact Vercel deployment URL. No trailing slash. Multiple origins: comma-separated. |
| `SESSION_SECURE` | `true` | Required for `SameSite=None` cookies over HTTPS |
| `SESSION_SAMESITE` | `None` | Required for cross-domain sessions (Vercel ↔ Railway) |
| `SESSION_DOMAIN` | *(leave blank)* | Only set if using a custom domain |
| `ALLOW_SETUP_SCRIPTS` | `false` | Set to `true` only during initial admin bootstrap, then immediately revert |
| `ADMIN_EMAIL` | `admin@yourdomain.com` | Admin bootstrap credential |
| `ADMIN_PASSWORD` | *(strong unique secret)* | Admin bootstrap credential — do not reuse local value |

---

## 6. Authentication & Security Model

### Admin Authentication

Admin users are stored in the `admin_users` table with columns `id`, `username`, `email`, `password` (bcrypt hash).

Login flow (`admin-dashboard/index.php`):
1. `POST` with `username` (accepts username or email) and `password`
2. Query: `SELECT ... FROM admin_users WHERE username = ? OR email = ?` (prepared statement)
3. `password_verify($input, $row['password'])` — bcrypt comparison
4. On success: set `$_SESSION['admin_logged_in'] = true`, `$_SESSION['admin_id']`, `$_SESSION['admin_username']`, `$_SESSION['admin_email']`
5. Redirect to `dashboard.php`

Every admin-only PHP page and API endpoint calls either `requireAdminLogin()` (for HTML pages, redirects to `index.php`) or `requireAdminSession()` (for API endpoints, returns HTTP 401 JSON).

### Student Authentication

Students are stored in the `students` table. Student IDs are auto-generated in the format `IPMC{YEAR}{sequence}` (e.g., `IPMC2024001`).

Login flow (via `student_login_api.php`):
1. `POST` JSON with `student_id` and `password`
2. Prepared statement lookup on `student_id`
3. `password_verify()` against stored bcrypt hash
4. On success: set `$_SESSION['student_logged_in'] = true`, `$_SESSION['student_id']`, `$_SESSION['student_name']`

Every student-facing API endpoint calls `requireStudentSession()` which:
1. Calls `startSession()`
2. Checks `$_SESSION['student_logged_in'] === true` and `isset($_SESSION['student_id'])`
3. Returns the session `student_id` to the caller
4. Returns HTTP 401 JSON immediately if the check fails

### Why Client-Supplied IDs Are Rejected

Student data endpoints (`get_student_data.php`, `get_student_results.php`, `get_student_grievances.php`, `submit_grievance_api.php`) ignore any `student_id` supplied in the request URL or body. The student identity comes **exclusively** from `$_SESSION['student_id']`. This prevents horizontal privilege escalation — a logged-in student cannot access another student's data by changing a URL parameter.

### Password Hashing

All passwords (admin and student) are hashed with `password_hash($plain, PASSWORD_DEFAULT)`, which uses bcrypt with a cost factor of 10 by default. Verification uses `password_verify()`. Plain-text passwords are never stored or logged.

### Session Security

Sessions are configured in `startSession()` inside `config.php`:

```php
session_set_cookie_params([
    'lifetime' => 0,          // Session cookie (expires on browser close)
    'path'     => '/',
    'secure'   => true/false, // From SESSION_SECURE env var
    'httponly' => true,        // Not accessible via JavaScript
    'samesite' => 'None'/'Lax', // From SESSION_SAMESITE env var
]);
```

For cross-domain deployment (Vercel frontend + Railway backend):
- `SESSION_SECURE=true` is required (cookie only sent over HTTPS)
- `SESSION_SAMESITE=None` is required (cookie sent on cross-site requests)
- The browser will only send the cookie if `credentials: 'include'` is set in all `fetch()` calls on the frontend

### Known Limitations

- **Ephemeral sessions on Railway**: File-based sessions are lost on service restart or redeploy. All logged-in students and admins are logged out. See [Limitations](#12-limitations--future-improvements).
- **No CSRF protection**: Admin form submissions and student API calls have no CSRF token. Mitigated by `SameSite` cookie policy, but not fully protected on same-site attack scenarios.
- **No rate limiting**: Login endpoints have no brute-force protection.

### Risks if Misconfigured

| Misconfiguration | Risk |
|---|---|
| `SESSION_SECURE=false` in production | Session cookie sent over HTTP, vulnerable to interception |
| `SESSION_SAMESITE=Lax` with cross-domain deployment | Session cookie not sent on cross-origin API calls; all student logins fail silently |
| `ALLOW_SETUP_SCRIPTS=true` in production | Debug/setup scripts publicly accessible, exposing DB structure and admin bootstrap |
| `FRONTEND_ORIGIN` left blank | CORS falls back to `Access-Control-Allow-Origin: *`, disabling credential-based CORS |

---

## 7. API Reference

All API endpoints live under `admin-dashboard/api/`. All responses are JSON with the shape:

```json
{
  "success": true | false,
  "message": "Human-readable message",
  "data": { ... } | null
}
```

HTTP status codes: `200` success, `400` validation error, `401` unauthenticated, `403` forbidden, `404` not found, `405` wrong method, `500` internal error.

---

### `POST /admin-dashboard/api/contact_form_api.php`

**Auth required**: No

**Purpose**: Saves a contact form submission (name, email, phone, program interest, campus, message).

**Request body** (JSON or `multipart/form-data`):

```json
{
  "first_name": "Kwame",
  "last_name": "Mensah",
  "email": "kwame@example.com",
  "phone": "0241234567",
  "program": "Software Engineering",
  "campus": "Accra",
  "message": "I would like more information about your programs."
}
```

**Response**:

```json
{ "success": true, "message": "Message received successfully!", "data": null }
```

---

### `POST /admin-dashboard/api/student_register_api.php`

**Auth required**: No

**Purpose**: Registers a new student. Auto-generates a student ID in the format `IPMC{YEAR}{sequence}` (e.g., `IPMC2024001`). Hashes the password with bcrypt before storage.

**Request body** (JSON):

```json
{
  "first_name": "Ama",
  "last_name": "Asante",
  "email": "ama.asante@email.com",
  "phone": "0501234567",
  "program": "Network Engineering",
  "password": "securepassword123"
}
```

**Response** (201):

```json
{
  "success": true,
  "message": "Registration successful",
  "data": { "student_id": "IPMC2024001" }
}
```

---

### `POST /admin-dashboard/api/student_login_api.php`

**Auth required**: No (this is the authentication endpoint)

**Purpose**: Authenticates a student and creates a PHP session.

**Request body** (JSON):

```json
{ "student_id": "IPMC2024001", "password": "securepassword123" }
```

**Response** (200):

```json
{
  "success": true,
  "message": "Login successful",
  "data": { "student_id": "IPMC2024001", "student_name": "Ama Asante" }
}
```

Sets a `PHPSESSID` cookie. Subsequent requests must include this cookie (`credentials: 'include'` in `fetch()`).

---

### `GET /admin-dashboard/api/check_session.php`

**Auth required**: No (returns current session state)

**Purpose**: Used by the frontend to check if a student session is still active without triggering a login redirect.

**Response** (200):

```json
{
  "success": true,
  "message": "Session active",
  "data": { "logged_in": true, "student_id": "IPMC2024001", "student_name": "Ama Asante" }
}
```

or:

```json
{ "success": false, "message": "Not logged in", "data": { "logged_in": false } }
```

---

### `GET /admin-dashboard/api/get_student_data.php`

**Auth required**: Student session (`requireStudentSession()`)

**Purpose**: Returns the authenticated student's profile, all their academic results, and their cumulative GPA. Ignores any `student_id` URL parameter — identity comes from session only.

**Response** (200):

```json
{
  "success": true,
  "message": "Student data retrieved",
  "data": {
    "student": {
      "student_id": "IPMC2024001",
      "first_name": "Ama",
      "last_name": "Asante",
      "email": "ama.asante@email.com",
      "phone": "0501234567",
      "program": "Network Engineering",
      "created_at": "2024-01-15 10:30:00"
    },
    "results": [
      {
        "course_code": "NE101",
        "course_name": "Introduction to Networking",
        "semester": "Semester 1",
        "academic_year": "2024/2025",
        "grade": "A",
        "credits": 3,
        "score": 92.5
      }
    ],
    "gpa": 3.85
  }
}
```

---

### `GET /admin-dashboard/api/get_student_results.php`

**Auth required**: Student session

**Purpose**: Returns the authenticated student's results with per-course grade points, total credits, and GPA. Structurally similar to `get_student_data.php` but includes `total_credits` and `total_courses`.

**Response** (200): Same as `get_student_data.php` plus:

```json
{ "total_credits": 18, "total_courses": 6 }
```

---

### `GET /admin-dashboard/api/get_student_grievances.php`

**Auth required**: Student session

**Purpose**: Returns all grievances submitted by the authenticated student, including admin responses.

**Response** (200):

```json
{
  "success": true,
  "message": "Grievances retrieved successfully",
  "data": {
    "grievances": [
      {
        "id": 1,
        "student_id": "IPMC2024001",
        "category": "Academic",
        "title": "Missing result for NE201",
        "description": "My result for NE201 was not uploaded for Semester 2.",
        "status": "replied",
        "admin_response": "The result has been uploaded. Please check your portal.",
        "created_at": "2024-11-10 14:22:00"
      }
    ],
    "count": 1
  }
}
```

---

### `POST /admin-dashboard/api/submit_grievance_api.php`

**Auth required**: Student session

**Purpose**: Submits a new grievance on behalf of the authenticated student.

**Request body** (JSON):

```json
{
  "category": "Academic",
  "title": "Missing result for NE201",
  "description": "My result for NE201 was not uploaded for Semester 2 2024/2025."
}
```

**Response** (200):

```json
{
  "success": true,
  "message": "Grievance submitted successfully! You will receive a response soon.",
  "data": { "grievance_id": 7 }
}
```

---

### `POST /admin-dashboard/api/upload_result_api.php`

**Auth required**: Admin session (`requireAdminSession()`)

**Purpose**: Uploads or updates a single student result. If a result already exists for the same `student_id` + `course_code` + `semester` + `academic_year`, it is updated rather than duplicated.

**Request body** (JSON):

```json
{
  "student_id": "IPMC2024001",
  "course_code": "NE101",
  "course_name": "Introduction to Networking",
  "semester": "Semester 1",
  "academic_year": "2024/2025",
  "grade": "A",
  "credits": 3,
  "score": 92.5
}
```

**Response** (200):

```json
{ "success": true, "message": "Result uploaded successfully", "data": null }
```

---

### `GET /admin-dashboard/api/get_student_data_admin.php`

**Auth required**: Admin session

**Purpose**: Returns any student's profile, results, and GPA by `student_id` URL parameter. Used by the admin panel's student detail modal. Accepts a `student_id` parameter because the admin is authorized to view any student's data.

**Query parameter**: `?student_id=IPMC2024001`

**Response**: Same shape as `get_student_data.php`.

---

### `GET /admin-dashboard/api/get_contact_courses.php`

**Auth required**: No

**Purpose**: Returns active courses flagged for the contact form dropdown (`show_in_contact_dropdown = 1`), ordered by `display_order`.

**Response** (200):

```json
{
  "success": true,
  "message": "Courses retrieved",
  "data": {
    "courses": [
      { "id": 1, "course_code": "NE", "course_name": "Network Engineering", "category": "career-dome" }
    ]
  }
}
```

---

### `POST /admin-dashboard/api/logout_api.php`

**Auth required**: No (safe to call regardless of session state)

**Purpose**: Destroys the current PHP session and clears the session cookie.

**Response** (200):

```json
{ "success": true, "message": "Logged out successfully", "data": null }
```

---

## 8. Deployment

### Backend — Railway

#### Step 1: Create a Railway Project

1. Log in at [railway.app](https://railway.app)
2. **New Project** → **Deploy from GitHub Repo**
3. Select your repository and branch (`main`)
4. Railway detects `railway.json` and sets the start command automatically:
   ```
   php -S 0.0.0.0:$PORT -t .
   ```

#### Step 2: Add a MySQL Database

1. Inside your Railway project: **+ New** → **Database** → **Add MySQL**
2. Railway creates a MySQL instance and automatically injects connection variables into your project environment:
   - `MYSQLHOST`, `MYSQLPORT`, `MYSQLUSER`, `MYSQLPASSWORD`, `MYSQLDATABASE`
3. In your PHP service: **Variables** → ensure the MySQL variables are referenced (Railway links them automatically when both services are in the same project)

#### Step 3: Set Environment Variables

In your Railway PHP service → **Variables**, add:

```
APP_ENV=production
FRONTEND_ORIGIN=https://your-app.vercel.app
SESSION_SECURE=true
SESSION_SAMESITE=None
SESSION_DOMAIN=
ALLOW_SETUP_SCRIPTS=false
ADMIN_EMAIL=admin@yourdomain.com
ADMIN_PASSWORD=<strong-unique-password>
```

#### Step 4: Import the Database Schema

Using the Railway CLI:

```bash
# Install Railway CLI
npm install -g @railway/cli

# Log in
railway login

# Link to your project
railway link

# Import schema
railway run "mysql -h $MYSQLHOST -P $MYSQLPORT -u $MYSQLUSER -p$MYSQLPASSWORD $MYSQLDATABASE" < database.sql
```

Or from your local machine with MySQL client installed:

```bash
# Get connection details from Railway dashboard (Variables tab of MySQL service)
mysql -h <MYSQLHOST> -P <MYSQLPORT> -u <MYSQLUSER> -p<MYSQLPASSWORD> <MYSQLDATABASE> < database.sql
```

#### Step 5: Bootstrap the Admin User

1. Temporarily set `ALLOW_SETUP_SCRIPTS=true` on the Railway PHP service (Variables tab)
2. Railway will redeploy automatically (or trigger a manual redeploy)
3. Visit: `https://your-app.up.railway.app/admin-dashboard/fix_admin.php`
4. Confirm you see "✅ Admin User Created Successfully!" and "Password verification test: PASSED"
5. **Immediately** revert `ALLOW_SETUP_SCRIPTS=false` and redeploy

#### Step 6: Verify Backend Health

```bash
# Setup scripts must be inaccessible
curl -I https://your-app.up.railway.app/admin-dashboard/fix_admin.php
# Expected: HTTP/2 403

# API must respond with JSON (not a PHP error)
curl https://your-app.up.railway.app/admin-dashboard/api/check_session.php
# Expected: {"success":false,"message":"Not logged in","data":{"logged_in":false}}

# Admin login page must load
curl -I https://your-app.up.railway.app/admin-dashboard/index.php
# Expected: HTTP/2 200
```

---

### Frontend — Vercel

#### Step 1: Configure the API Base URL

Before deploying, add a `<meta>` tag to every HTML page that calls the API. In `contact.html` and `grievance.html`, insert inside `<head>` **before** the `frontend-config.js` script tag:

```html
<meta name="api-base-url" content="https://your-app.up.railway.app/admin-dashboard/api/">
```

Replace `your-app.up.railway.app` with your actual Railway service URL.

**This is required.** `frontend-config.js` throws a hard `Error` if no API base URL is configured when the page is not running on localhost. The contact form and student portal will be completely broken without this tag.

#### Step 2: Verify `.vercelignore`

Confirm `.vercelignore` contains the following (it already does by default):

```
admin-dashboard/
database.sql
.env
.env.*
railway.json
test_results.html
*.log
```

This ensures no PHP files, database dumps, or secrets are deployed to Vercel.

#### Step 3: Deploy to Vercel

**Via Vercel Dashboard (recommended)**:
1. Log in at [vercel.com](https://vercel.com)
2. **Add New Project** → **Import Git Repository**
3. Select your repository
4. Configure:
   - **Framework Preset**: Other
   - **Root Directory**: `.` (project root)
   - **Build Command**: *(leave empty)*
   - **Output Directory**: `.` (project root)
   - **Install Command**: *(leave empty)*
5. Click **Deploy**

**Via Vercel CLI**:
```bash
npm install -g vercel
cd C:\xampp\htdocs\final-project
vercel --prod
```

#### Step 4: Verify the Deployment

1. Open your Vercel URL, e.g., `https://your-app.vercel.app`
2. Open browser DevTools → **Network** tab
3. Load `contact.html` — the course dropdown fetch should show a request to `your-app.up.railway.app`, not to `vercel.app`
4. Load `grievance.html` — register or log in as a student; confirm no CORS errors in the console
5. Confirm `https://your-app.vercel.app/test_results.html` → **404 Not Found** (correctly excluded)

---

## 9. Post-Deployment Verification

```
RAILWAY BACKEND
[ ] https://<railway-url>/admin-dashboard/index.php         → HTTP 200, login page renders
[ ] https://<railway-url>/admin-dashboard/api/check_session.php → HTTP 200, JSON {"logged_in":false}
[ ] https://<railway-url>/admin-dashboard/fix_admin.php     → HTTP 403 Forbidden
[ ] https://<railway-url>/admin-dashboard/setup_admin.php   → HTTP 403 Forbidden
[ ] https://<railway-url>/admin-dashboard/debug_all.php     → HTTP 403 Forbidden
[ ] https://<railway-url>/admin-dashboard/test_connection.php → HTTP 403 Forbidden
[ ] Admin login with ADMIN_EMAIL / ADMIN_PASSWORD           → redirects to dashboard.php
[ ] Admin dashboard shows student/course/result counts
[ ] POST to student_login_api.php with wrong password       → HTTP 401 JSON
[ ] POST to get_student_data.php without session            → HTTP 401 JSON

VERCEL FRONTEND
[ ] https://<vercel-url>/                                   → homepage loads, no JS errors
[ ] https://<vercel-url>/contact.html                       → course dropdown loads from Railway
[ ] https://<vercel-url>/contact.html form submission       → success message appears
[ ] https://<vercel-url>/grievance.html                     → login/register UI renders
[ ] Student registration on grievance.html                  → returns student ID
[ ] Student login                                           → dashboard renders with results
[ ] Student grievance submission                            → appears in admin grievances panel
[ ] Admin grievance reply                                   → visible on student portal
[ ] Network tab: all API requests go to Railway domain
[ ] https://<vercel-url>/test_results.html                  → 404

CROSS-ORIGIN SESSION CHECK
[ ] After student login, Set-Cookie header includes:        SameSite=None; Secure
[ ] Student portal pages send cookie on API calls           (credentials: 'include' in fetch)
[ ] Student logout clears session                           → subsequent API calls return 401
```

---

## 10. Common Issues & Fixes

### "mysqli extension not found" / blank page

**Cause**: PHP is running without the `mysqli` extension.

**Fix (XAMPP)**: Open `C:\xampp\php\php.ini`, find and uncomment:

```ini
extension=mysqli
```

Restart Apache.

**Fix (PHP CLI / Railway)**: Ensure PHP is installed with `--with-mysqli`. On Railway, the default PHP buildpack includes `mysqli`.

---

### Admin login fails immediately after setup

**Cause**: The `admin_users` table is empty, or the password was hashed at a different PHP version.

**Fix**: Re-run `fix_admin.php` with `ALLOW_SETUP_SCRIPTS=true`. Verify the "Password verification test: PASSED" message appears before reverting the flag.

---

### "IPMC_CONFIG is not defined" / contact form broken on Vercel

**Cause**: `<meta name="api-base-url">` is missing from the HTML page, and the page is not on localhost.

**Fix**: Add to the `<head>` of `contact.html` and `grievance.html`, before `<script src="frontend-config.js">`:

```html
<meta name="api-base-url" content="https://your-app.up.railway.app/admin-dashboard/api/">
```

---

### Student login works locally but not on Vercel

**Cause**: Cross-domain cookies are being blocked. The session cookie is not sent because `SameSite=Lax` does not allow cross-origin requests.

**Fix**: Set on Railway:

```
SESSION_SECURE=true
SESSION_SAMESITE=None
```

And confirm `FRONTEND_ORIGIN` matches your exact Vercel URL (no trailing slash). Also verify all `fetch()` calls in the frontend use `credentials: 'include'`.

---

### "Permission denied" when writing session files

**Cause**: The `storage/sessions/` directory doesn't exist or isn't writable.

**Fix**: `startSession()` calls `@mkdir($savePath, 0775, true)` automatically. If it still fails, check that the Railway service's filesystem allows writes to the working directory. Alternatively, set `SESSION_SAVE_PATH` to an empty string to use PHP's default session directory.

---

### Folder name has a space (e.g., `FINAL PROJECT`)

**Cause**: PHP's built-in server and some URL-encoded paths break when the document root contains spaces.

**Fix**: Rename the folder to `final-project` (no spaces). Update any bookmarks to `localhost/final-project/`.

---

### CORS error: "Access-Control-Allow-Origin header missing"

**Cause**: `FRONTEND_ORIGIN` on Railway does not match the actual Vercel origin, or it includes a trailing slash.

**Fix**: Set `FRONTEND_ORIGIN` to exactly the origin as the browser sends it — no trailing slash, no path:

```
FRONTEND_ORIGIN=https://your-app.vercel.app
```

If you have a custom domain on Vercel, add it comma-separated:

```
FRONTEND_ORIGIN=https://your-app.vercel.app,https://www.ipmc.edu.gh
```

---

### "Student not found" after login

**Cause**: The registered student exists in the `students` table, but a subsequent query fails to find them, often because the `student_id` format changed or a test entry was inserted manually with a non-standard ID.

**Fix**: Log in to the admin dashboard → Students. Confirm the student's ID matches the `IPMC{YEAR}{sequence}` format exactly. IDs are case-sensitive.

---

## 11. Security Considerations

### SQL Injection

All database queries use MySQLi prepared statements with `bind_param()`. No user-supplied value is ever concatenated directly into a SQL string. Dynamic search queries build the `WHERE` clause with `?` placeholders:

```php
$query .= " AND (course_name LIKE ? OR course_code LIKE ?)";
$params[] = '%' . $search . '%';
$types .= 'ss';
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
```

Category and status filters are validated against hardcoded allowlists before use:

```php
$allowedCategories = ['career-dome', 'university', 'certification', 'short-courses'];
if ($category_filter && !in_array($category_filter, $allowedCategories, true)) {
    $category_filter = '';
}
```

### Hardcoded Credentials

No credentials exist in the codebase. Admin credentials are read exclusively from `ADMIN_EMAIL` and `ADMIN_PASSWORD` environment variables at runtime. The `.env` file is gitignored and excluded from both Vercel and Railway deployments.

### Environment-Based Configuration

All sensitive values — DB credentials, session parameters, CORS origins, admin bootstrap values — are loaded from environment variables. `config.php` loads `.env` only if the file exists and is readable, with no fatal error if it is absent (Railway provides values directly via the environment).

### Debug and Setup Scripts

Nine PHP files (`fix_admin.php`, `setup_admin.php`, `hash.php`, `hash_password.php`, `generate_hash.php`, `debug_all.php`, `check_admin.php`, `check_results.php`, `test_connection.php`) are gated by:

```php
if (getenv('ALLOW_SETUP_SCRIPTS') !== 'true') {
    http_response_code(403);
    exit('Forbidden');
}
```

In production, `ALLOW_SETUP_SCRIPTS` must be `false` (the default). These scripts are also excluded from Railway deployments via `.railwayignore`. Even if the env var were accidentally set, they would be absent from the deployed filesystem.

### Output Encoding

All user-supplied data rendered in admin HTML pages is passed through `htmlspecialchars()`. Database error messages are written to `error_log()` and never echoed to the browser. Internal PHP errors are suppressed in production (`APP_ENV=production` sets `display_errors=0`).

### CORS Configuration

`configureCors()` reads `FRONTEND_ORIGIN` from the environment and compares it against the incoming `Origin` request header. If the origin matches, it sets `Access-Control-Allow-Origin` to that exact origin (not `*`) and includes `Access-Control-Allow-Credentials: true`. If no `FRONTEND_ORIGIN` is set and no origin header is present (e.g., server-to-server requests), it falls back to `Access-Control-Allow-Origin: *`. This fallback does not affect credential-bearing requests — browsers reject `*` with credentials and require an exact origin.

### `.env` Not Deployed

- `.gitignore` excludes `.env` and `.env.*` (with `!.env.example` exception)
- `.vercelignore` excludes `.env` and `.env.*`
- `.railwayignore` excludes `.env` and `.env.*`

Railway injects production secrets directly into the process environment, so no `.env` file is needed or present on the Railway filesystem.

---

## 12. Limitations & Future Improvements

### Session Persistence

PHP file-based sessions are stored in `storage/sessions/`. Railway's filesystem is ephemeral — sessions are wiped on every deploy or service restart, logging out all active users. 

**Recommended fix**: Use a Redis session handler or a database-backed session. Add a Railway Redis service and configure `session.save_handler=redis` and `session.save_path=tcp://...` via `SESSION_SAVE_HANDLER` and `SESSION_SAVE_PATH` environment variables, and update `startSession()` accordingly.

### No Rate Limiting

Login endpoints (`student_login_api.php`, `admin-dashboard/index.php`) have no brute-force protection. An attacker can make unlimited login attempts.

**Recommended fix**: Track failed attempts in a `login_attempts` table or Redis, block IPs or accounts after N failures within a time window.

### No CSRF Tokens

Admin form submissions (course add/edit, result upload, grievance reply) use standard HTML forms with no CSRF token. The `SameSite=Lax` cookie policy provides partial mitigation for cross-site form submissions, but this is not a complete defense.

**Recommended fix**: Generate a per-session CSRF token in `startSession()`, embed it in all forms via a hidden input, and verify it on every POST.

### No Audit Logging

Admin actions (result uploads, grievance replies, student deletions) are not logged to a persistent store. There is no audit trail.

**Recommended fix**: Add an `audit_log` table and write a record for every write action by admin users.

### No Database Migration System

Schema changes require manual SQL execution. There is no versioned migration tool.

**Recommended fix**: Adopt a lightweight migration system (plain numbered SQL files applied in order) or a PHP migration library.

### Bulk CSV Upload Has No Validation

The CSV bulk result upload in `results.php` reads columns by position (`$data[0]`, `$data[1]`, ...) and attempts to insert each row directly. Malformed CSV files, missing columns, or invalid grades are silently counted as errors with no row-level feedback.

**Recommended fix**: Validate each CSV row before insertion and return a per-row error report.

### No Email Notifications

Grievance submissions and admin replies have no email notification. Students must manually check the portal to see responses.

**Recommended fix**: Integrate a transactional email service (e.g., SendGrid, Mailgun) to notify students when grievances are replied to.

---

## License

This project was developed as a final year academic project for IPMC University College. Contact the repository owner for usage terms.
