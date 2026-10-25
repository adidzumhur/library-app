# Entity-Relationship Diagram (draft)

![ERD](erd.png)

The PNG is generated from `erd.dot` (Graphviz): `dot -Tpng docs/erd.dot -o docs/erd.png`.
The same model as a Mermaid diagram (rendered automatically by GitHub):

```mermaid
erDiagram
    USERS ||--o{ LOANS : borrows
    BOOKS ||--o{ LOANS : "is loaned in"
    USERS ||--o{ REVIEWS : writes
    BOOKS ||--o{ REVIEWS : has
    AUTHORS ||--o{ BOOKS : writes
    CATEGORIES ||--o{ BOOKS : contains

    USERS {
        int id PK
        varchar first_name
        varchar last_name
        varchar email UK
        varchar password_hash
        enum role "admin | member"
        datetime created_at
    }
    AUTHORS {
        int id PK
        varchar first_name
        varchar last_name
        varchar nationality
        int birth_year
        text biography
    }
    CATEGORIES {
        int id PK
        varchar name UK
        varchar description
    }
    BOOKS {
        int id PK
        varchar title
        varchar isbn UK
        int publication_year
        text description
        varchar cover_url
        int total_copies
        int available_copies
        int author_id FK
        int category_id FK
    }
    LOANS {
        int id PK
        int user_id FK
        int book_id FK
        date loan_date
        date due_date
        date return_date
        enum status "borrowed | returned | overdue"
    }
    REVIEWS {
        int id PK
        int user_id FK
        int book_id FK
        tinyint rating "1-5"
        text comment
        datetime created_at
    }
```

## Relationships

| Relationship | Type | Foreign key |
|---|---|---|
| An author writes many books | 1 : N | `books.author_id → authors.id` |
| A category contains many books | 1 : N | `books.category_id → categories.id` |
| A user has many loans | 1 : N | `loans.user_id → users.id` |
| A book appears in many loans | 1 : N | `loans.book_id → books.id` |
| A user writes many reviews | 1 : N | `reviews.user_id → users.id` |
| A book has many reviews | 1 : N | `reviews.book_id → books.id` |

`loans` and `reviews` also act as the link between `users` and `books` (a many-to-many relationship resolved through these two tables).
