# BookNest – Library Management System

> **Status:** Milestone 2 – MySQL database with sample data and DAO layer.
> Milestone 1: project structure, static SPA frontend and ERD.

BookNest is a single-page web application for managing a small library. Members can browse the catalog, borrow books and write reviews. Administrators manage books, authors, categories, loans and users. New books can be imported automatically by ISBN using the Open Library API.

Course: IT 2001 – Web Programming, International Burch University.

## Technology stack

| Layer | Technology |
|---|---|
| Frontend | HTML, CSS, JavaScript, jQuery, Bootstrap 5 (hash-based SPA) |
| Backend | PHP 8, FlightPHP (REST API), PDO |
| Database | MySQL |
| Auth | JWT (Milestone 4) |
| API docs | OpenAPI / Swagger (Milestone 3) |

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

## Project structure

```
library-app/
├── backend/
│   ├── config/        # config.example.php (config.php is git-ignored)
│   ├── dao/           # Data access layer: Database, BaseDao + one DAO per entity
│   ├── tests/         # dao_test.php - CRUD test for all DAOs
│   ├── services/      # Business logic (Milestone 3)
│   ├── routes/        # FlightPHP routes (Milestone 3)
│   ├── middleware/    # Auth, roles, logging, errors (Milestone 4)
│   └── logs/          # info.log, error.log (git-ignored)
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
   The script creates the `library_db` database with all tables and sample data. Terminal alternative:
   ```bash
   mysql -u root -p < database/library_db.sql
   ```
2. **Create the configuration file.** Copy `backend/config/config.example.php` to `backend/config/config.php` and fill in your database credentials (the defaults work with a fresh XAMPP installation: user `root`, empty password). `config.php` is git-ignored and must never be committed.
3. **Test the DAO layer.** Open http://localhost/<repository-name>/backend/tests/dao_test.php. It runs create, read, update and delete for every entity, deletes its own test records at the end and prints `Result: N passed, 0 failed`.
4. From Milestone 3: run `composer install` in `backend/`.

## Links

- Swagger documentation: _TBD (Milestone 3)_
- Deployed application: _TBD (Milestone 5)_
