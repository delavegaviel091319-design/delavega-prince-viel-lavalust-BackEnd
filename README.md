# Product Management API  (LavaLust)

Backend for **Laboratory Exercise No. 6 - CRUD with Authentication using React/Vue.js and LavaLust API**
and for the **Database Migration** laboratory activity. Built on LavaLust 4.6.

| Layer | Technology |
|---|---|
| API | LavaLust (PHP) - `Api` library, JWT access + refresh tokens |
| Database | MySQL (Aiven in production, any MySQL/MariaDB locally) |
| Hosting | Render (Docker) |
| Frontend | `../product-frontend` (React) |

## 1. Run it locally

```bash
cp .env.example .env            # then edit DB_* values
php lava jwt:generate           # fills JWT_SECRET and REFRESH_TOKEN_KEY in .env
php lava migration run          # creates migrations, users, refresh_tokens, products
php lava serve --port=3000      # API on http://localhost:3000
```

Create the database first (e.g. `CREATE DATABASE product_db;`) and put its name in `DB_NAME`.
Check it works: open <http://localhost:3000/api/health>.

## 2. Migration laboratory (CLI -> Controller -> Route -> Migration library -> Database)

| Item | File |
|---|---|
| Controller | `app/controllers/MigrationController.php` |
| Routes | `app/config/routes.php` (bottom of the file) |
| Custom CLI command | `app/commands/Migration.php` |
| Migration files | `app/migrations/000..005_*.php` |

```bash
php lava migration status
php lava migration run
php lava migration rollback
php lava migration rollback-all     # DEV DATABASE ONLY
php lava migration refresh          # DEV DATABASE ONLY
php lava migration create-migration add_something_table
```

Migrations `004` and `005` adapt older `users` tables for API authentication
and registration without removing existing user or product data.

The same operations are available in the browser while `MIGRATION_ENABLED=true`:
`/status`, `/migrate`, `/rollback`, `/rollback-all`, `/refresh`, `/create-migration/{name}`.
**Keep `MIGRATION_ENABLED` off (or unset) on Render** so nobody can wipe your database from a URL.

> `php lava make:command` creates the command inside `app/commands/` (plural) - that is the folder
> this LavaLust version scans, so the file lives there instead of `app/command/`.

## 3. API reference

Base URL: `http://localhost:3000` (local) or your Render URL. All bodies are JSON.

### Authentication
| Method | Endpoint | Body | Notes |
|---|---|---|---|
| POST | `/api/auth/register` | `username, email, password` | creates a user (role `user`) |
| POST | `/api/auth/login` | `username` (or email), `password` | returns `user` + `tokens.access_token` / `tokens.refresh_token` |
| POST | `/api/auth/refresh` | `refresh_token` | rotates both tokens |
| POST | `/api/auth/logout` | `refresh_token` | needs Bearer token; revokes the refresh token |
| GET  | `/api/auth/me` | - | needs Bearer token |

### Products (every request needs `Authorization: Bearer <access_token>`)
| Method | Endpoint | Body | Result |
|---|---|---|---|
| GET | `/api/products` | - | list |
| GET | `/api/products/{id}` | - | one product |
| POST | `/api/products` | `product_name, description, price, quantity` | 201 + created product |
| PUT / PATCH | `/api/products/{id}` | same (PATCH accepts any subset) | updated product |
| DELETE | `/api/products/{id}` | - | success message |

Without a valid token every product endpoint answers `401 {"error":"Unauthorized"}`.

Quick test with curl:
```bash
curl -X POST http://localhost:3000/api/auth/login -H "Content-Type: application/json" \
     -d '{"username":"admin","password":"Admin@12345"}'
curl http://localhost:3000/api/products -H "Authorization: Bearer <access_token>"
```
(You can also use <https://api-tester.marasigan.dev/>.)

## 4. Environment variables

| Variable | Purpose |
|---|---|
| `DB_DRIVER, DB_HOST, DB_PORT, DB_USER, DB_PASSWORD, DB_NAME, DB_CHARSET` | database connection |
| `DB_SSL` | `true` for Aiven (encrypted connection) |
| `DB_SSL_CA` / `DB_SSL_CA_CONTENT` | Aiven CA certificate: file path, or the PEM text itself (use this on Render) |
| `JWT_SECRET`, `REFRESH_TOKEN_KEY` | signing keys, at least 32 random characters and different from each other |
| `CORS_ORIGIN` | comma separated list of frontend URLs allowed to call the API |
| `MIGRATION_ENABLED` | `true` only on your own computer |
| `APP_ENV` | `development` or `production` |

`.env` is git-ignored. **Never commit database passwords.**

## 5. Deploy to Render

See `../docs/LAB6_SUBMISSION_GUIDE.md` (Aiven + Render step by step).

## Changes made to the framework (so you can explain them)
* `scheme/libraries/Api.php` - fixed `database()` call (`call->database()`), JSON `Content-Type` header.
* `scheme/kernel/Routine.php` - `handle_cors()` loads the api config so browser pre-flight (OPTIONS) requests succeed.
* `scheme/database/Database.php` - optional SSL (`DB_SSL`) required by Aiven.
* `app/config/api.php`, `app/config/migration.php` - now read from `.env`.
* `Dockerfile` - Apache listens on Render's `$PORT`.
