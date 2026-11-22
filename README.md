# BookNest – Library Management System

> **Status:** Milestone 3: services with business logic, FlightPHP REST routes for all entities, OpenAPI/Swagger documentation and the Open Library integration.
> Milestone 2: MySQL database and DAO layer. Milestone 1: project structure, static SPA frontend and ERD.

BookNest is a single-page web application for managing a small library. Members can browse the catalog, borrow books and write reviews. Administrators manage books, authors, categories, loans and users. New books can be imported automatically by ISBN using the Open Library API.

Course: IT 2001 – Web Programming, International Burch University.

## Technology stack

| Layer | Technology |
|---|---|
| Frontend | HTML, CSS, JavaScript, jQuery, Bootstrap 5 (hash-based SPA) |
| Backend | PHP 8, FlightPHP (REST API), PDO |
| Database | MySQL |
| Auth | JWT (Milestone 4) |
| API docs | OpenAPI annotations (zircote/swagger-php) + Swagger UI |

## Entities and relationships

| Entity | Description |
|---|---|
| `users` | Library members and administrators |
| `authors` | Book authors |
| `categories` | Book genres / categories |
| `books` | Books in the catalog (belongs to one author and optionally one category) |
| `loans` | A user borrowing a book (loan date, due date, return date, status) |
| `reviews` | A user's rating (1–5) and comment for a book |

- An author has many books; a category has many books.
- A user has many loans and many reviews.
- A book has many loans and many reviews.

Full ERD: [docs/ERD.md](docs/ERD.md)

## Roles

| Role | Permissions |
|---|---|
| **admin** | Full CRUD on all entities, manages users and loans, imports books via ISBN |
| **member** | Views books, authors and categories; borrows books; creates/edits own reviews; edits own profile |

Test accounts (included in the sample data; login is implemented in Milestone 4):

| Role | Email | Password |
|---|---|---|
| admin | admin@booknest.com | admin123 |
| member | emir@example.com | member123 |

## External API

**Open Library API** (https://openlibrary.org/developers/api) – used for the *Import by ISBN* feature on the Books page. The admin enters an ISBN, the backend fetches the title, author, publication year and cover, and the admin can add the book to the library with one click. No API key is required.

| Endpoint | Purpose |
|---|---|
| `GET /openlibrary/isbn/{isbn}` | Preview of the book from Open Library, plus whether it is already in our library |
| `POST /openlibrary/import` | Adds the book; reuses the author if one with the same name exists, otherwise creates it |

The frontend never calls Open Library directly: it calls our backend, and `OpenLibraryService` calls Open Library with a timeout (`external_api.timeout` in the config file, 5 seconds by default).
If Open Library is slow, down or returns an error, the failure is written to `backend/logs/error.log` and the user gets `503` with a friendly message; the rest of the application keeps working and books can still be added manually. Successful and not-found lookups are written to `backend/logs/info.log`.

## Project structure

```
library-app/
├── backend/
│   ├── config/        # config.example.php (config.php is git-ignored)
│   ├── dao/           # Data access layer: Database, BaseDao + one DAO per entity
│   ├── tests/         # dao_test.php - CRUD test for all DAOs
│   ├── services/      # Business logic: one service per entity + OpenLibraryService
│   ├── routes/        # FlightPHP routes with OpenAPI annotations
│   ├── utils/         # ApiException, Validator, Logger
│   ├── swagger/       # Swagger UI (index.html) + OpenAPI generator (swagger.php)
│   ├── middleware/    # Auth, roles, logging, errors (Milestone 4)
│   ├── logs/          # info.log, error.log (git-ignored)
│   ├── index.php      # API entry point: registers services, error handler, routes
│   └── .htaccess      # Sends all requests to index.php
├── frontend/
│   ├── index.html     # SPA shell
│   ├── css/
│   ├── js/            # app.js = SPA router
│   ├── views/         # HTML templates loaded by the router
│   └── assets/
├── database/
│   └── library_db.sql # Schema + sample data
├── docs/              # ERD, AI_LOG.md
├── AGENTS.md
└── README.md
```

## Running locally

The frontend loads views with AJAX, so it must be served over HTTP (opening `index.html` directly from disk will not work).

**Option A – XAMPP:** clone the repository into `htdocs`, start Apache in the XAMPP Control Panel and open `http://localhost/<repository-name>/frontend/`.

**Option B – PHP built-in server**
```bash
php -S localhost:8000 -t frontend
```
Then open http://localhost:8000.

### Database and backend setup

1. **Import the database.** Start Apache and MySQL in XAMPP, open http://localhost/phpmyadmin, click **Import**, choose `database/library_db.sql` and click **Go** (Import / Upload).
   The script creates the `library_db` database with all tables and sample data (it is safe to import again; it drops and recreates the tables). Terminal alternative:
   ```bash
   mysql -u root -p < database/library_db.sql
   ```
2. **Create the configuration file.** Copy `backend/config/config.example.php` to `backend/config/config.php` and fill in your database credentials (the defaults work with a fresh XAMPP installation: user `root`, empty password). `config.php` is git-ignored and must never be committed.
3. **Install the PHP dependencies.** In a terminal, inside the `backend/` folder, run `composer install`. This creates `backend/vendor/` (FlightPHP and swagger-php), which is git-ignored.
4. **Test the DAO layer.** Open http://localhost/<repository-name>/backend/tests/dao_test.php. It runs create, read, update and delete for every entity, deletes its own test records at the end and prints `Result: N passed, 0 failed`.
5. **Open the API.** http://localhost/<repository-name>/backend/ should return `{"name":"BookNest API","status":"running",...}`. The API uses Apache `mod_rewrite` and the PHP `curl` extension; both are enabled in a default XAMPP installation.

### REST API

Base URL (local): `http://localhost/<repository-name>/backend`. All requests and responses are JSON.

| Resource | Endpoints |
|---|---|
| Books | `GET /books` (`?search=`, `?category_id=`, `?author_id=`), `GET /books/{id}`, `GET /books/{id}/reviews`, `POST /books`, `PUT /books/{id}`, `DELETE /books/{id}` |
| Authors | `GET /authors` (`?search=`), `GET /authors/{id}`, `GET /authors/{id}/books`, `POST`, `PUT`, `DELETE /authors/{id}` |
| Categories | `GET /categories`, `GET /categories/{id}`, `GET /categories/{id}/books`, `POST`, `PUT`, `DELETE /categories/{id}` |
| Loans | `GET /loans` (`?status=`), `GET /loans/{id}`, `POST /loans` (borrow), `PUT /loans/{id}` (change due date), `PUT /loans/{id}/return`, `DELETE /loans/{id}` |
| Reviews | `GET /reviews`, `GET /reviews/{id}`, `POST`, `PUT`, `DELETE /reviews/{id}` |
| Users | `GET /users`, `GET /users/{id}`, `GET /users/{id}/loans`, `POST`, `PUT`, `DELETE /users/{id}` |
| Open Library | `GET /openlibrary/isbn/{isbn}`, `POST /openlibrary/import` |

Status codes: `200` OK, `201` created, `400` validation error or broken business rule, `404` not found, `500` unexpected server error, `503` Open Library unavailable. `401` and `403` are added with JWT in Milestone 4.
Errors look like `{"message": "Validation failed.", "errors": {"title": "title is required."}}`. Unexpected errors never show SQL, file paths or stack traces to the client; the details are written to `backend/logs/error.log`.

Main business rules (in `backend/services/`):
- A book's `available_copies` is managed by the server: borrowing takes a copy, returning gives it back (in a database transaction). A book cannot be borrowed when no copy is left, or twice by the same member at the same time.
- An author with books, or a book with active loans, cannot be deleted.
- Emails, category names and ISBNs are unique; a member can review a book only once; ratings are 1 to 5.
- Passwords are stored only as bcrypt hashes and are never returned by the API.

## Links

- Swagger documentation (local): http://localhost/<repository-name>/backend/swagger/ (public link after deployment in Milestone 5)
- Deployed application: _TBD (Milestone 5)_
