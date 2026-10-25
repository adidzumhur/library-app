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
