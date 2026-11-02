-- =====================================================================
-- BookNest - Library Management System
-- MySQL / MariaDB database schema with sample data
--
-- Import (phpMyAdmin): Import -> choose this file -> Go
-- Import (terminal):   mysql -u root -p < database/library_db.sql
--
-- Test accounts (passwords are stored as bcrypt hashes):
--   admin@booknest.com  / admin123   (role: admin)
--   emir@example.com    / member123  (role: member)
--   lejla@example.com   / member123  (role: member)
--   tarik@example.com   / member123  (role: member)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS library_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE library_db;

-- Drop tables in reverse order of dependencies (child tables first)
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS loans;
DROP TABLE IF EXISTS books;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS authors;
DROP TABLE IF EXISTS users;

-- ---------------------------------------------------------------------
-- users: library members and administrators
-- ---------------------------------------------------------------------
CREATE TABLE users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  first_name    VARCHAR(50)  NOT NULL,
  last_name     VARCHAR(50)  NOT NULL,
  email         VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('admin', 'member') NOT NULL DEFAULT 'member',
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- authors
-- ---------------------------------------------------------------------
CREATE TABLE authors (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  first_name  VARCHAR(50) NOT NULL,
  last_name   VARCHAR(50) NOT NULL,
  nationality VARCHAR(50) NULL,
  birth_year  SMALLINT    NULL,
  biography   TEXT        NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- categories
-- ---------------------------------------------------------------------
CREATE TABLE categories (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name        VARCHAR(50)  NOT NULL,
  description VARCHAR(255) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categories_name (name)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- books: each book has one author and (optionally) one category
-- ---------------------------------------------------------------------
CREATE TABLE books (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title            VARCHAR(150) NOT NULL,
  isbn             VARCHAR(13)  NULL,
  publication_year SMALLINT     NULL,
  description      TEXT         NULL,
  cover_url        VARCHAR(255) NULL,
  total_copies     INT UNSIGNED NOT NULL DEFAULT 1,
  available_copies INT UNSIGNED NOT NULL DEFAULT 1,
  author_id        INT UNSIGNED NOT NULL,
  category_id      INT UNSIGNED NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_books_isbn (isbn),
  KEY idx_books_author (author_id),
  KEY idx_books_category (category_id),
  -- An author with books cannot be deleted (delete or reassign the books first)
  CONSTRAINT fk_books_author FOREIGN KEY (author_id)
    REFERENCES authors (id) ON DELETE RESTRICT ON UPDATE CASCADE,
  -- Deleting a category keeps the books, they just have no category
  CONSTRAINT fk_books_category FOREIGN KEY (category_id)
    REFERENCES categories (id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT chk_books_copies CHECK (available_copies <= total_copies)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- loans: a user borrowing a book
-- ---------------------------------------------------------------------
CREATE TABLE loans (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id     INT UNSIGNED NOT NULL,
  book_id     INT UNSIGNED NOT NULL,
  loan_date   DATE NOT NULL,
  due_date    DATE NOT NULL,
  return_date DATE NULL,
  status      ENUM('borrowed', 'returned', 'overdue') NOT NULL DEFAULT 'borrowed',
  PRIMARY KEY (id),
  KEY idx_loans_user (user_id),
  KEY idx_loans_book (book_id),
  CONSTRAINT fk_loans_user FOREIGN KEY (user_id)
    REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_loans_book FOREIGN KEY (book_id)
    REFERENCES books (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT chk_loans_dates CHECK (due_date >= loan_date)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- reviews: a user's rating (1-5) and comment for a book
-- ---------------------------------------------------------------------
CREATE TABLE reviews (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED NOT NULL,
  book_id    INT UNSIGNED NOT NULL,
  rating     TINYINT UNSIGNED NOT NULL,
  comment    TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  -- A user can review the same book only once
  UNIQUE KEY uq_reviews_user_book (user_id, book_id),
  KEY idx_reviews_book (book_id),
  CONSTRAINT fk_reviews_user FOREIGN KEY (user_id)
    REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_reviews_book FOREIGN KEY (book_id)
    REFERENCES books (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT chk_reviews_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB;

-- =====================================================================
-- Sample data
-- =====================================================================

INSERT INTO users (id, first_name, last_name, email, password_hash, role, created_at) VALUES
  (1, 'Admin', 'User',  'admin@booknest.com', '$2y$10$UwPCYOQRoqcYY5Z7IdLZ3.BdhaimJsttf.DUvaCP.11maWftB0Z0m', 'admin',  '2026-09-01 10:00:00'),
  (2, 'Emir',  'Kovač', 'emir@example.com',   '$2y$10$6bzwJX9DUN6cQzSn9scGAOFB0OySIecqA5VNrO2ktvTRfu3m3W4Bq', 'member', '2026-09-12 14:20:00'),
  (3, 'Lejla', 'Begić', 'lejla@example.com',  '$2y$10$6bzwJX9DUN6cQzSn9scGAOFB0OySIecqA5VNrO2ktvTRfu3m3W4Bq', 'member', '2026-09-20 09:45:00'),
  (4, 'Tarik', 'Mehić', 'tarik@example.com',  '$2y$10$6bzwJX9DUN6cQzSn9scGAOFB0OySIecqA5VNrO2ktvTRfu3m3W4Bq', 'member', '2026-09-28 17:10:00');

INSERT INTO authors (id, first_name, last_name, nationality, birth_year, biography) VALUES
  (1, 'George',     'Orwell',   'British',  1903, 'English novelist and essayist, known for Animal Farm and Nineteen Eighty-Four.'),
  (2, 'J.R.R.',     'Tolkien',  'British',  1892, 'English writer and philologist, author of The Hobbit and The Lord of the Rings.'),
  (3, 'Robert C.',  'Martin',   'American', 1952, 'Software engineer and author, known as Uncle Bob.'),
  (4, 'Yuval Noah', 'Harari',   'Israeli',  1976, 'Historian and author of Sapiens and Homo Deus.'),
  (5, 'Meša',       'Selimović','Bosnian',  1910, 'Writer from Tuzla, author of Death and the Dervish.'),
  (6, 'Ivo',        'Andrić',   'Yugoslav', 1892, 'Nobel Prize-winning writer, author of The Bridge on the Drina.');

INSERT INTO categories (id, name, description) VALUES
  (1, 'Fiction',     'Novels and short stories'),
  (2, 'Fantasy',     'Magic, myths and other worlds'),
  (3, 'Programming', 'Software development books'),
  (4, 'History',     'Historical non-fiction'),
  (5, 'Classics',    'Classic works of literature');

INSERT INTO books (id, title, isbn, publication_year, description, cover_url, total_copies, available_copies, author_id, category_id) VALUES
  (1,  '1984',                      '9780451524935', 1949, 'A dystopian novel about a totalitarian regime that uses surveillance and propaganda.', NULL, 5, 4, 1, 1),
  (2,  'Animal Farm',               '9780451526342', 1945, 'An allegorical novella about a farm revolution gone wrong.',                          NULL, 3, 3, 1, 1),
  (3,  'The Hobbit',                '9780547928227', 1937, 'Bilbo Baggins joins a quest to reclaim a treasure guarded by a dragon.',              NULL, 2, 1, 2, 2),
  (4,  'The Fellowship of the Ring','9780547928210', 1954, 'The first part of The Lord of the Rings.',                                            NULL, 2, 2, 2, 2),
  (5,  'Clean Code',                '9780132350884', 2008, 'A handbook of agile software craftsmanship.',                                         NULL, 2, 2, 3, 3),
  (6,  'The Clean Coder',           '9780137081073', 2011, 'A code of conduct for professional programmers.',                                     NULL, 1, 1, 3, 3),
  (7,  'Sapiens',                   '9780062316097', 2011, 'A brief history of humankind.',                                                       NULL, 3, 2, 4, 4),
  (8,  'Death and the Dervish',     '9780810112971', 1966, 'A dervish in Ottoman Sarajevo struggles with power and justice.',                     NULL, 2, 2, 5, 5),
  (9,  'The Bridge on the Drina',   '9780226020457', 1945, 'Four centuries of history around the bridge in Višegrad.',                           NULL, 2, 2, 6, 5),
  (10, 'Homo Deus',                 '9780062464316', 2015, 'A brief history of tomorrow.',                                                        NULL, 1, 1, 4, 4);

INSERT INTO loans (id, user_id, book_id, loan_date, due_date, return_date, status) VALUES
  (1, 2, 1, '2026-10-19', '2026-11-02', NULL,         'borrowed'),
  (2, 3, 3, '2026-10-06', '2026-10-20', NULL,         'overdue'),
  (3, 4, 5, '2026-10-01', '2026-10-15', '2026-10-14', 'returned'),
  (4, 1, 7, '2026-10-22', '2026-11-05', NULL,         'borrowed'),
  (5, 3, 2, '2026-09-21', '2026-10-05', '2026-10-03', 'returned'),
  (6, 2, 8, '2026-09-15', '2026-09-29', '2026-09-30', 'returned');

INSERT INTO reviews (id, user_id, book_id, rating, comment, created_at) VALUES
  (1, 3, 1, 5, 'A must-read. Still relevant today.',            '2026-10-12 18:30:00'),
  (2, 2, 1, 4, 'Dark but brilliant. The ending stays with you.', '2026-10-15 21:05:00'),
  (3, 4, 5, 4, 'Very useful for every programmer.',             '2026-10-16 11:40:00'),
  (4, 3, 2, 5, 'Short, simple and powerful.',                   '2026-10-04 08:15:00'),
  (5, 2, 8, 5, 'One of the best Bosnian novels.',               '2026-10-01 19:50:00'),
  (6, 4, 3, 4, 'A great adventure, perfect for any age.',       '2026-10-20 16:25:00');
