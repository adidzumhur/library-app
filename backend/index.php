<?php
/**
 * Entry point of the REST API. Every request is sent here by .htaccess.
 *
 * Request path: route (routes/) -> service (services/) -> DAO (dao/) -> database
 */

require_once __DIR__ . '/vendor/autoload.php';

// Loan dates (today, due date) and log timestamps use local time
date_default_timezone_set('Europe/Sarajevo');

require_once __DIR__ . '/utils/ApiException.php';
require_once __DIR__ . '/utils/Logger.php';

require_once __DIR__ . '/services/AuthorService.php';
require_once __DIR__ . '/services/CategoryService.php';
require_once __DIR__ . '/services/BookService.php';
require_once __DIR__ . '/services/LoanService.php';
require_once __DIR__ . '/services/ReviewService.php';
require_once __DIR__ . '/services/UserService.php';
require_once __DIR__ . '/services/OpenLibraryService.php';

// Services are registered once and used in the routes as Flight::bookService(), etc.
// Flight creates the object the first time it is used (lazy loading).
Flight::register('authorService', 'AuthorService');
Flight::register('categoryService', 'CategoryService');
Flight::register('bookService', 'BookService');
Flight::register('loanService', 'LoanService');
Flight::register('reviewService', 'ReviewService');
Flight::register('userService', 'UserService');
Flight::register('openLibraryService', 'OpenLibraryService');

/**
 * Every error is returned as JSON.
 * - ApiException: an expected error (400, 404, 503...) with a message that is safe to show.
 * - Anything else (e.g. PDOException): logged with details, but the client gets only a
 *   general message, never the SQL query, file path or stack trace.
 * Milestone 4 moves this into the error handling middleware.
 */
Flight::map('error', function (Throwable $e) {
    if ($e instanceof ApiException) {
        $body = ['message' => $e->getMessage()];
        if (!empty($e->getErrors())) {
            $body['errors'] = $e->getErrors();
        }
        Flight::jsonHalt($body, $e->getStatusCode());
        return;
    }

    $request = Flight::request();
    Logger::error("{$request->method} {$request->url} 500 " . get_class($e) . ': ' . $e->getMessage()
        . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')');
    Flight::jsonHalt(['message' => 'Something went wrong on the server. Please try again later.'], 500);
});

// Unknown URL
Flight::map('notFound', function () {
    Flight::jsonHalt(['message' => 'Endpoint not found.'], 404);
});

/**
 * @OA\Get(
 *     path="/",
 *     tags={"general"},
 *     summary="API status and link to the documentation",
 *     @OA\Response(response=200, description="The API is running")
 * )
 */
Flight::route('GET /', function () {
    Flight::json(['name' => 'BookNest API', 'status' => 'running', 'docs' => 'swagger/']);
});

require_once __DIR__ . '/routes/author_routes.php';
require_once __DIR__ . '/routes/category_routes.php';
require_once __DIR__ . '/routes/book_routes.php';
require_once __DIR__ . '/routes/loan_routes.php';
require_once __DIR__ . '/routes/review_routes.php';
require_once __DIR__ . '/routes/user_routes.php';
require_once __DIR__ . '/routes/openlibrary_routes.php';

Flight::start();
