<?php

require_once __DIR__ . '/../utils/ApiException.php';
require_once __DIR__ . '/../utils/Logger.php';
require_once __DIR__ . '/BookService.php';
require_once __DIR__ . '/../dao/AuthorDao.php';
require_once __DIR__ . '/../dao/BookDao.php';

/**
 * External API: Open Library (https://openlibrary.org/dev/docs/api/books). No API key is needed.
 *
 * Feature: "Import by ISBN" on the Books page.
 *   1. lookup() - the admin enters an ISBN and sees a preview (title, author, year, cover)
 *   2. import() - the admin adds the book to the library with one click
 *
 * Failure handling (required by the project):
 *   - a timeout from the config file, so a slow API cannot block our backend
 *   - every failure is written to the error log
 *   - the user gets a friendly message (503), never the technical error
 *   - the rest of the application keeps working; books can still be added manually
 */
class OpenLibraryService
{
    private string $baseUrl;
    private int $timeout;
    private BookService $bookService;
    private BookDao $bookDao;
    private AuthorDao $authorDao;

    public function __construct()
    {
        $config = require __DIR__ . '/../config/config.php';
        $this->baseUrl = rtrim($config['external_api']['open_library_base_url'], '/');
        $this->timeout = (int) $config['external_api']['timeout'];

        $this->bookService = new BookService();
        $this->bookDao     = new BookDao();
        $this->authorDao   = new AuthorDao();
    }

    /** Book data from Open Library for one ISBN, plus whether the book is already in our library */
    public function lookup(string $isbn): array
    {
        $isbn = BookService::normalizeIsbn($isbn);
        if (!BookService::isValidIsbn($isbn)) {
            throw new ApiException('Validation failed.', 400, ['isbn' => 'isbn must have 10 or 13 digits (ISBN-10 may end with X).']);
        }

        $data = $this->fetchBook($isbn);

        $existing = $this->bookDao->getByIsbn($isbn);
        $data['already_in_library'] = $existing !== null;
        $data['book_id'] = $existing !== null ? (int) $existing['id'] : null;
        return $data;
    }

    /**
     * Adds the book found on Open Library to our database.
     * The author is reused if one with the same name exists, otherwise it is created.
     */
    public function import(array $input): array
    {
        $isbn = BookService::normalizeIsbn((string) ($input['isbn'] ?? ''));
        $found = $this->lookup($isbn); // validates the ISBN and calls the API

        if ($found['already_in_library']) {
            throw new ApiException("This book is already in the library (book id {$found['book_id']}).", 400);
        }

        // Transaction: if the book cannot be saved (e.g. the category does not exist),
        // the newly created author is removed too, so no author without books is left behind.
        $this->authorDao->beginTransaction();
        try {
            [$firstName, $lastName] = $this->splitName($found['author_name']);
            $author = $this->authorDao->getByFullName($firstName, $lastName)
                ?? $this->authorDao->insert(['first_name' => $firstName, 'last_name' => $lastName]);

            // BookService::create validates everything again (category, copies...) like a manual add
            $book = $this->bookService->create([
                'title'            => mb_substr($found['title'], 0, 150),
                'isbn'             => $isbn,
                'publication_year' => $found['publication_year'],
                'cover_url'        => $found['cover_url'],
                'description'      => $found['subjects'] ? 'Subjects: ' . implode(', ', $found['subjects']) : null,
                'author_id'        => (int) $author['id'],
                'category_id'      => $input['category_id'] ?? null,
                'total_copies'     => $input['total_copies'] ?? 1,
            ]);
            $this->authorDao->commit();
            return $book;
        } catch (Throwable $e) {
            $this->authorDao->rollBack();
            throw $e;
        }
    }

    /** Calls the Open Library Books API and returns only the fields our application uses */
    private function fetchBook(string $isbn): array
    {
        $url = $this->baseUrl . '/api/books?' . http_build_query([
            'bibkeys' => "ISBN:$isbn",
            'format'  => 'json',
            'jscmd'   => 'data',
        ]);

        $start = microtime(true);
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,            // return the body instead of printing it
            CURLOPT_CONNECTTIMEOUT => $this->timeout,  // max seconds to connect
            CURLOPT_TIMEOUT        => $this->timeout,  // max seconds for the whole request
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT      => 'BookNest/1.0 (IT 2001 Web Programming student project)',
        ]);
        $body     = curl_exec($curl);
        $status   = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);
        $ms = (int) round((microtime(true) - $start) * 1000);

        // 1. No response at all (timeout, DNS error, no internet...)
        if ($body === false) {
            $this->fail("Open Library lookup ISBN $isbn failed: $curlError ({$ms}ms)");
        }
        // 2. The API answered with an error status
        if ($status !== 200) {
            $this->fail("Open Library lookup ISBN $isbn failed: HTTP $status ({$ms}ms)");
        }
        // 3. The answer is not valid JSON
        $json = json_decode($body, true);
        if (!is_array($json)) {
            $this->fail("Open Library lookup ISBN $isbn failed: invalid JSON response ({$ms}ms)");
        }

        // The answer looks like {"ISBN:9780451524935": {...}}, or {} when the ISBN is unknown
        $book = $json["ISBN:$isbn"] ?? null;
        if ($book === null) {
            Logger::info("Open Library lookup ISBN $isbn: not found ({$ms}ms)");
            throw new ApiException("No book with ISBN $isbn was found on Open Library.", 404);
        }
        Logger::info("Open Library lookup ISBN $isbn: found \"{$book['title']}\" ({$ms}ms)");

        return [
            'isbn'             => $isbn,
            'title'            => $book['title'] ?? 'Untitled',
            'author_name'      => $book['authors'][0]['name'] ?? 'Unknown author',
            'publication_year' => $this->extractYear($book['publish_date'] ?? ''),
            'publishers'       => array_column($book['publishers'] ?? [], 'name'),
            'number_of_pages'  => $book['number_of_pages'] ?? null,
            'cover_url'        => $book['cover']['medium'] ?? null,
            'subjects'         => array_slice(array_column($book['subjects'] ?? [], 'name'), 0, 5),
        ];
    }

    /** Writes the technical reason to the error log and gives the user a friendly message */
    private function fail(string $logMessage): never
    {
        Logger::error($logMessage);
        throw new ApiException('Open Library is not available right now. Please try again later or add the book manually.', 503);
    }

    /** publish_date can be "1950", "July 1950" or "June 8, 1949"; we keep only the 4-digit year */
    private function extractYear(string $publishDate): ?int
    {
        return preg_match('/\b(\d{4})\b/', $publishDate, $m) ? (int) $m[1] : null;
    }

    /**
     * "George Orwell" -> ["George", "Orwell"], "J.R.R. Tolkien" -> ["J.R.R.", "Tolkien"].
     * The last word is the last name. A one-word name ("Homer") gets an empty first name.
     */
    private function splitName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName));
        $lastName = array_pop($parts);
        return [mb_substr(implode(' ', $parts), 0, 50), mb_substr($lastName, 0, 50)];
    }
}
