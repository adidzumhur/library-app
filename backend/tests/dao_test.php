<?php
/**
 * Manual test for the DAO layer (Milestone 2).
 *
 * Runs Create, Read, Update and Delete for every entity and prints the results.
 * Every record it creates is deleted at the end, so the sample data stays the same.
 *
 * Run in the browser: http://localhost/library-app/backend/tests/dao_test.php
 * or in a terminal:   php backend/tests/dao_test.php
 */

require_once __DIR__ . '/../dao/UserDao.php';
require_once __DIR__ . '/../dao/AuthorDao.php';
require_once __DIR__ . '/../dao/CategoryDao.php';
require_once __DIR__ . '/../dao/BookDao.php';
require_once __DIR__ . '/../dao/LoanDao.php';
require_once __DIR__ . '/../dao/ReviewDao.php';

if (PHP_SAPI !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

$passed = 0;
$failed = 0;

function check(string $description, bool $condition): void
{
    global $passed, $failed;
    echo ($condition ? '[OK]   ' : '[FAIL] ') . $description . PHP_EOL;
    $condition ? $passed++ : $failed++;
}

try {
    $userDao     = new UserDao();
    $authorDao   = new AuthorDao();
    $categoryDao = new CategoryDao();
    $bookDao     = new BookDao();
    $loanDao     = new LoanDao();
    $reviewDao   = new ReviewDao();

    echo "== READ (sample data) ==" . PHP_EOL;
    check('users: ' . count($userDao->getAll()) . ' rows', count($userDao->getAll()) > 0);
    check('authors: ' . count($authorDao->getAll()) . ' rows', count($authorDao->getAll()) > 0);
    check('categories: ' . count($categoryDao->getAll()) . ' rows', count($categoryDao->getAll()) > 0);
    check('books: ' . count($bookDao->getAll()) . ' rows', count($bookDao->getAll()) > 0);
    check('loans: ' . count($loanDao->getAll()) . ' rows', count($loanDao->getAll()) > 0);
    check('reviews: ' . count($reviewDao->getAll()) . ' rows', count($reviewDao->getAll()) > 0);
    check('getById(999999) returns null for a missing row', $bookDao->getById(999999) === null);

    $book = $bookDao->getByIdWithDetails(1);
    check("book 1 with details: \"{$book['title']}\" by {$book['author_name']} ({$book['category_name']})", $book['author_name'] !== null);
    check('admin found by email', $userDao->getByEmail('admin@booknest.com')['role'] === 'admin');

    echo PHP_EOL . "== CREATE ==" . PHP_EOL;
    $user = $userDao->insert([
        'first_name' => 'Test', 'last_name' => 'User', 'email' => 'test.dao@example.com',
        'password_hash' => password_hash('test123', PASSWORD_BCRYPT), 'role' => 'member',
    ]);
    check("user created (id {$user['id']})", $user['email'] === 'test.dao@example.com');

    $author = $authorDao->insert(['first_name' => 'Test', 'last_name' => 'Author', 'nationality' => 'Bosnian', 'birth_year' => 1990]);
    check("author created (id {$author['id']})", $author['last_name'] === 'Author');

    $category = $categoryDao->insert(['name' => 'Test Category', 'description' => 'Created by dao_test.php']);
    check("category created (id {$category['id']})", $category['name'] === 'Test Category');

    $newBook = $bookDao->insert([
        'title' => 'Test Book', 'isbn' => '0000000000001', 'publication_year' => 2026,
        'total_copies' => 2, 'available_copies' => 2,
        'author_id' => $author['id'], 'category_id' => $category['id'],
    ]);
    check("book created (id {$newBook['id']})", $newBook['title'] === 'Test Book');

    $loan = $loanDao->insert([
        'user_id' => $user['id'], 'book_id' => $newBook['id'],
        'loan_date' => '2026-11-01', 'due_date' => '2026-11-15', 'status' => 'borrowed',
    ]);
    check("loan created (id {$loan['id']})", $loan['status'] === 'borrowed');

    $review = $reviewDao->insert(['user_id' => $user['id'], 'book_id' => $newBook['id'], 'rating' => 4, 'comment' => 'Test review']);
    check("review created (id {$review['id']})", (int) $review['rating'] === 4);

    echo PHP_EOL . "== UPDATE ==" . PHP_EOL;
    check('user role changed to admin', $userDao->update($user['id'], ['role' => 'admin'])['role'] === 'admin');
    check('author nationality updated', $authorDao->update($author['id'], ['nationality' => 'Croatian'])['nationality'] === 'Croatian');
    check('category description updated', $categoryDao->update($category['id'], ['description' => 'Updated'])['description'] === 'Updated');
    check('book title updated', $bookDao->update($newBook['id'], ['title' => 'Updated Test Book'])['title'] === 'Updated Test Book');
    check('loan marked as returned', $loanDao->update($loan['id'], ['status' => 'returned', 'return_date' => '2026-11-10'])['status'] === 'returned');
    check('review rating updated', (int) $reviewDao->update($review['id'], ['rating' => 5])['rating'] === 5);
    check('update of a missing id returns null', $bookDao->update(999999, ['title' => 'X']) === null);

    echo PHP_EOL . "== CUSTOM QUERIES ==" . PHP_EOL;
    check('available copies decreased (borrow)', $bookDao->changeAvailableCopies($newBook['id'], -1));
    check('available copies cannot go above total_copies', !$bookDao->changeAvailableCopies($newBook['id'], +5));
    check('book found by ISBN', $bookDao->getByIsbn('0000000000001') !== null);
    check('search "orwell" finds books', count($bookDao->search('orwell')) > 0);
    check('loans of user 2: ' . count($loanDao->getByUser(2)), count($loanDao->getByUser(2)) > 0);
    $summary = $reviewDao->getRatingSummary(1);
    check("rating summary for book 1: {$summary['average']} ({$summary['count']} reviews)", $summary['count'] > 0);

    echo PHP_EOL . "== SECURITY ==" . PHP_EOL;
    $evil = $bookDao->search("' OR 1=1 --");
    check('SQL injection in search returns no rows', count($evil) === 0);
    check('search for "_" does not match every book (LIKE wildcards escaped)', count($bookDao->search('_')) === 0);
    check('search for "%" does not match every book', count($authorDao->searchByName('%')) === 0);
    try {
        $bookDao->update($newBook['id'], ['title; DROP TABLE books; --' => 'x']);
        check('unknown column name is rejected', false);
    } catch (InvalidArgumentException $e) {
        check('unknown column name is rejected', true);
    }

    echo PHP_EOL . "== DELETE ==" . PHP_EOL;
    check('review deleted', $reviewDao->delete($review['id']));
    check('loan deleted', $loanDao->delete($loan['id']));
    check('book deleted', $bookDao->delete($newBook['id']));
    check('category deleted', $categoryDao->delete($category['id']));
    check('author deleted', $authorDao->delete($author['id']));
    check('user deleted', $userDao->delete($user['id']));
    check('deleting a missing id returns false', $bookDao->delete(999999) === false);

    try {
        $authorDao->delete(1);   // George Orwell still has books
        check('author with books cannot be deleted (foreign key)', false);
    } catch (PDOException $e) {
        check('author with books cannot be deleted (foreign key)', true);
    }
} catch (Throwable $e) {
    $failed++;
    echo PHP_EOL . '[ERROR] ' . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "Result: $passed passed, $failed failed" . PHP_EOL;
