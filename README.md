# BookNest – Library Management System

> **Status:** Milestone 1 (draft) – project structure, static SPA frontend and ERD.

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
| `books` | Books in the catalog (belongs to one author and one category) |
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

Test accounts *(will be added in Milestone 4)*:

| Role | Email | Password |
|---|---|---|
| admin | admin@booknest.com | _TBD_ |
| member | member@booknest.com | _TBD_ |

## External API

**Open Library API** (https://openlibrary.org/developers/api) – used for the *Import by ISBN* feature on the Books page. The admin enters an ISBN, the backend fetches the title, author, publication year and cover, and the admin can add the book to the library with one click. No API key is required.

## Project structure

```
library-app/
├── backend/
│   ├── config/        # config.example.php (config.php is git-ignored)
│   ├── dao/           # Data access layer (Milestone 2)
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

Backend setup (from Milestone 2):
1. Import the `.sql` file into MySQL.
2. Copy `backend/config/config.example.php` to `backend/config/config.php` and fill in your database credentials and JWT secret.
3. Run `composer install` in `backend/`.

## Links

- Swagger documentation: _TBD (Milestone 3)_
- Deployed application: _TBD (Milestone 5)_
