# AI Usage Log

This log documents how AI tools are used in the BookNest project: which tools, for which parts, the key prompts, how the output was verified, and where the AI was wrong.

## Approach

AI is used as an assistant, not as a decision-maker. The rules I follow:

1. **Requirements first.** Every prompt is based on the official project document or lab instructions, not on general requests.
2. **I make the decisions.** Topic, entities, roles and the external API were chosen by me from the options discussed.
3. **Rules for the AI are written down.** `AGENTS.md` defines the architecture, folder structure and naming conventions the AI must follow.
4. **Every output is verified** against the milestone requirements before it is committed.
5. **I must be able to explain every file.** Anything I cannot explain is reviewed again before submission.

---

## Milestone 1 – Setup, static frontend, ERD

### Tools

| Tool | Used for |
|---|---|
| Claude (claude.ai, chat) | Topic analysis, project scaffolding, static SPA frontend, ERD draft, documentation drafts |

### Division of work

| Area | AI contribution | My contribution |
|---|---|---|
| Topic and scope | Analysed the requirements and proposed three topics (library, recipes, court reservations) | Chose the library topic and the external API (Open Library) |
| Data model | Drafted the ERD for the chosen entities | Decided on the six entities and their relationships |
| Project structure | Generated the folder structure, `.gitignore` and `config.example.php` | Checked it against Milestone 1 requirement 1 (backend/frontend folders, git-ignored secrets and logs) |
| Frontend | Generated the SPA shell, router and Bootstrap views | Reviewed it against the Lab 1 SPA instructions, which led to a fix (see Issue 1) |
| Documentation | Drafted README and AGENTS.md | Reviewed them; AGENTS.md is the rulebook for the following milestones |
| Repository | – | Created the GitHub repository, the `milestone1` branch and the pull request |

### Key prompts

**1. Requirements analysis and topic selection**
> "Here is the project requirements document. Suggest a simpler topic that satisfies all project requirements: at least 5 entities with CRUD, 2 roles and an external API."

*Outcome:* three proposals. I chose the library because its relationships are clear (author → book, user → loan → book), which makes the request trace easy to explain, and Open Library needs no API key.

**2. Milestone 1 scaffolding**
> "Create Milestone 1 for the library app: folder structure (backend: routes, services, dao, middleware, config, logs; frontend: css, js, views, assets), .gitignore, a static Bootstrap SPA with all pages including the place for the external API feature, an ERD with the entities, a README draft and AGENTS.md."

*Outcome:* the initial project structure, static SPA and documentation drafts.

**3. Compliance check against lab instructions**
> "Here is the Lab 1 document. Check whether the project matches its requirements."

*Outcome:* found that the router did not follow the SPA rule from the lab (Issue 1).

**4. Compliance check for AI documentation**
> "What does the project require for AI usage documentation, and is it covered by AGENTS.md and AI_LOG.md?"

*Outcome:* confirmed that both files contain all required sections.

### Verification

- [x] Folder structure compared with Milestone 1 requirement 1
- [x] SPA behaviour compared with the Lab 1 instructions
- [x] `config.php`, logs and `vendor/` excluded from Git through `.gitignore`
- [x] ERD contains at least 5 entities with primary and foreign keys

### Issues found in AI output

**Issue 1 – SPA views were reloaded on every navigation**
- **What was wrong:** The first version of the router (`frontend/js/app.js`) loaded the HTML template from `views/` with an AJAX request every time the user opened a page. The Lab 1 instructions require that each view is loaded only once, when the application starts.
- **How I noticed:** By comparing the generated router with the Lab 1 SPA instructions.
- **How it was fixed:** The router now loads all views once at startup into hidden `<section>` elements; navigation only shows the selected section and hides the others. Verified by checking that the server received exactly one request per view while navigating between pages.

**Issue 2 – Inconsistent sample data in the generated views**
- **What was wrong:** The AI generated different publication years for the same book on two pages: the *Import by ISBN* preview (`frontend/views/books.html`) showed "1984" as published in 1950, while the book details page (`frontend/views/book-details.html`) showed 1949. AI-generated placeholder data cannot be assumed to be correct or consistent.
- **How I noticed:** During a review of the generated pages before submission, by comparing the information shown for the same book on different pages.
- **How it was fixed:** Corrected the year in `books.html` to 1949, so both pages show the same data. From Milestone 4 all data will come from the database through the API, so there will be no hard-coded duplicates.

---

## Milestone 2 – Database and DAO layer

### Tools

| Tool | Used for |
|---|---|
| Claude (claude.ai, chat) | SQL schema and sample data, DAO classes, DAO test script, documentation updates |

### Division of work

| Area | AI contribution | My contribution |
|---|---|---|
| Database schema | Wrote `database/library_db.sql` from the ERD | Defined the entities and relationships in Milestone 1; approved the delete rules (e.g. an author with books cannot be deleted) |
| Sample data | Generated users, authors, categories, books, loans and reviews | Checked that the data is consistent (available copies match active loans) |
| DAO layer | Wrote `Database.php`, `BaseDao.php` and six entity DAOs | Checked them against Milestone 2 requirement 2 (base DAO, CRUD, prepared statements, config file) |
| Testing | Wrote `backend/tests/dao_test.php` | Imported the `.sql` file and ran the test locally |
| Documentation | Updated README, AGENTS.md and ERD | Reviewed the changes |

### Key prompts

**1. Database from the ERD**
> "Create the MySQL database for BookNest from the ERD in docs/ERD.md: primary and foreign keys, sensible ON DELETE rules, and an exported .sql file with realistic sample data, including one admin and several members with hashed passwords."

*Outcome:* `database/library_db.sql` with 6 tables, foreign keys, UNIQUE and CHECK constraints, and sample data.

**2. DAO layer**
> "Implement the DAO layer following AGENTS.md: a Database class that reads credentials from backend/config/config.php, a BaseDao with reusable CRUD methods using prepared statements, and one DAO per entity with the queries the frontend pages will need."

*Outcome:* `BaseDao` with `getAll`, `getById`, `insert`, `update`, `delete`; entity DAOs with extra queries (e.g. books with author and category names, loans of one user, average rating of a book).

**3. Verification**
> "Write a test script that runs create, read, update and delete for every DAO, cleans up after itself, and also checks SQL injection and foreign key behaviour."

*Outcome:* `backend/tests/dao_test.php` – all checks pass on a fresh import of the `.sql` file.

### Verification

- [x] `library_db.sql` imports into an empty MySQL/MariaDB server without errors
- [x] 6 tables with primary keys, foreign keys and sample data
- [x] `BaseDao` with reusable CRUD methods; every entity DAO extends it
- [x] All queries use prepared statements
- [x] Credentials are read from `backend/config/config.php`; only `config.example.php` is committed
- [x] `dao_test.php`: all checks pass, and running it twice gives the same result (test data is cleaned up)

### Issues found in AI output

**Issue 1 – Search matched every row when the term contained `_` or `%`**
- **What was wrong:** The first version of `BookDao::search()` and `AuthorDao::searchByName()` built the pattern as `"%$term%"`. In SQL `LIKE`, `_` and `%` are wildcards, so searching for `_` or `%` returned all 10 books instead of none. Prepared statements protect against SQL injection, but they do not escape `LIKE` wildcards.
- **How I noticed:** While testing the search queries with special characters, `search("_")` returned every book.
- **How it was fixed:** Added `BaseDao::likePattern()`, which escapes `%`, `_` and `\` before building the pattern, and used it in both DAOs. Two checks were added to `dao_test.php` so the bug cannot come back. The rule was also added to `AGENTS.md`.

**Issue 2 – The Milestone 1 ERD did not match the real database**
- **What was wrong:** The AI-generated draft ERD from Milestone 1 did not define what happens to dependent rows when a parent row is deleted, did not stop a user from reviewing the same book twice, and used different column types than the final schema (e.g. `INT` instead of `SMALLINT` for years, `category_id` shown as required).
- **How I noticed:** When the real tables were created, the questions "what happens to the books if an author is deleted?" and "can a user rate the same book twice?" had no answer in the ERD.
- **How it was fixed:** Defined the delete rules (`RESTRICT` for authors with books, `SET NULL` for categories, `CASCADE` for loans and reviews), added a UNIQUE key on `reviews (user_id, book_id)` and CHECK constraints, and updated `docs/ERD.md`, `erd.dot` and `erd.png` to match `library_db.sql`. The rule "schema changes must update the ERD" was added to `AGENTS.md`.
