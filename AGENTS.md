# AGENTS.md – context for AI coding assistants

## Project
BookNest is a library management single-page application (IT 2001 – Web Programming, IBU).
Members browse and borrow books and write reviews; admins manage all data. Books can be
imported by ISBN from the Open Library API.

## Technology stack
- Frontend: plain HTML, CSS, JavaScript, jQuery, Bootstrap 5. No frontend frameworks (no React/Vue).
- Backend: PHP 8 + FlightPHP REST API. Database access only through PDO with prepared statements.
- Database: MySQL.
- Auth: JWT (firebase/php-jwt), roles `admin` and `member`.
- API documentation: OpenAPI annotations + Swagger UI.

## Architecture rules
1. Frontend and backend are strictly separated. The backend returns only JSON; the frontend contains no PHP.
2. Backend layers, called only in this order: **route → service → DAO → database**.
   - Routes: read the request, call a service, return JSON with the correct HTTP status code.
   - Services: validation and business logic. Only services may call DAOs.
   - DAOs: SQL only. Every DAO extends `BaseDao`, which holds the reusable CRUD methods.
3. Middleware is registered in FlightPHP and reused; never copy auth/role checks into individual routes.
4. Frontend MVC: only files in `frontend/js/services/` make AJAX calls; views never call the API directly.
5. The SPA router is in `frontend/js/app.js`; each page is a template in `frontend/views/` and is loaded via the URL hash.
6. Secrets (DB credentials, JWT secret, API keys) live only in `backend/config/config.php`, which is git-ignored. Update `config.example.php` when adding new keys.
7. Error responses must never expose stack traces, SQL queries or file paths.

## Folder structure
```
backend/  config/ dao/ services/ routes/ middleware/ logs/
frontend/ index.html css/ js/ views/ assets/
docs/     ERD.md erd.dot erd.png AI_LOG.md
```

## Naming conventions
- Database tables and columns: `snake_case`, plural table names (`books`, `loans`).
- PHP classes: `PascalCase` with suffix by layer – `BookDao`, `BookService`; one class per file, file name = class name.
- Route files: `book_routes.php`, etc.
- REST endpoints: plural nouns, e.g. `GET /books`, `GET /books/{id}`, `POST /books`, `PUT /books/{id}`, `DELETE /books/{id}`.
- JavaScript: `camelCase` for variables/functions, `PascalCase` for service objects (`BookService`).
- HTML element ids: `kebab-case` (`book-form`).

## Rules for the AI
- Keep code simple and readable; the author must be able to explain every line at the defense.
- Do not add new libraries or frameworks without asking.
- Follow the layer order and naming conventions above.
- Always use prepared statements; never concatenate user input into SQL.
- Add short comments explaining non-obvious logic.
- Do not commit or print secrets.
