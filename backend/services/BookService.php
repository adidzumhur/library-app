<?php

require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/BookDao.php';
require_once __DIR__ . '/../dao/AuthorDao.php';
require_once __DIR__ . '/../dao/CategoryDao.php';
require_once __DIR__ . '/../dao/LoanDao.php';
require_once __DIR__ . '/../dao/ReviewDao.php';

class BookService extends BaseService
{
    protected string $entityName = 'Book';
    private AuthorDao $authorDao;
    private CategoryDao $categoryDao;
    private LoanDao $loanDao;
    private ReviewDao $reviewDao;

    public function __construct()
    {
        parent::__construct(new BookDao());
        $this->authorDao   = new AuthorDao();
        $this->categoryDao = new CategoryDao();
        $this->loanDao     = new LoanDao();
        $this->reviewDao   = new ReviewDao();
    }

    /**
     * Books with author and category names.
     * Optional filters from the query string: ?search=, ?category_id=, ?author_id=
     */
    public function getAll(array $filters = []): array
    {
        if (!empty($filters['search'])) {
            return $this->dao->search(trim($filters['search']));
        }
        if (!empty($filters['category_id'])) {
            return $this->dao->getByCategory((int) $filters['category_id']);
        }
        if (!empty($filters['author_id'])) {
            return $this->dao->getByAuthor((int) $filters['author_id']);
        }
        return $this->dao->getAllWithDetails();
    }

    /** One book with author and category names and its rating summary (Book details page) */
    public function getById(int $id): array
    {
        $book = $this->dao->getByIdWithDetails($id);
        if ($book === null) {
            throw new ApiException('Book not found.', 404);
        }
        $book['rating'] = $this->reviewDao->getRatingSummary($id); // ['average' => 4.5, 'count' => 2]
        return $book;
    }

    /**
     * available_copies is never sent by the client: a new book has no loans,
     * so all copies are available. Later it is changed only by loans (borrow/return).
     */
    public function create(array $data): array
    {
        $clean = $this->validate($data, false);
        $this->checkRelations($clean);
        if ($clean['isbn'] !== null) {
            $this->checkUniqueIsbn($clean['isbn']);
        }

        $clean['total_copies']     = $clean['total_copies'] ?? 1;
        $clean['available_copies'] = $clean['total_copies'];

        $book = $this->dao->insert($clean);
        return $this->getById((int) $book['id']);
    }

    public function update(int $id, array $data): array
    {
        $book = $this->dao->getById($id);
        if ($book === null) {
            throw new ApiException('Book not found.', 404);
        }

        $clean = $this->validate($data, true);
        if (empty($clean)) {
            throw new ApiException('No valid fields to update.', 400);
        }
        $this->checkRelations($clean);
        if (!empty($clean['isbn'])) {
            $this->checkUniqueIsbn($clean['isbn'], $id);
        }

        // When total_copies changes, the number of borrowed copies stays the same,
        // e.g. 5 total, 4 available (1 borrowed) -> new total 3 -> 2 available.
        if (isset($clean['total_copies'])) {
            $borrowed = $book['total_copies'] - $book['available_copies'];
            if ($clean['total_copies'] < $borrowed) {
                throw new ApiException('Validation failed.', 400, [
                    'total_copies' => "total_copies cannot be lower than the number of borrowed copies ($borrowed).",
                ]);
            }
            $clean['available_copies'] = $clean['total_copies'] - $borrowed;
        }

        $this->dao->update($id, $clean);
        return $this->getById($id);
    }

    /** A book that is currently borrowed cannot be deleted (its loans would be lost) */
    public function delete(int $id): void
    {
        $this->getById($id);

        $activeLoans = $this->loanDao->countActiveByBook($id);
        if ($activeLoans > 0) {
            throw new ApiException("This book has $activeLoans active loan(s). It can be deleted after all copies are returned.", 400);
        }
        parent::delete($id);
    }

    /**
     * Removes hyphens and spaces: "978-0-451-52493-5" -> "9780451524935".
     * Public and static because OpenLibraryService uses it too.
     */
    public static function normalizeIsbn(string $isbn): string
    {
        return strtoupper(str_replace(['-', ' '], '', trim($isbn)));
    }

    /** ISBN-10 (9 digits + digit or X) or ISBN-13 (13 digits) */
    public static function isValidIsbn(string $isbn): bool
    {
        return preg_match('/^(\d{9}[\dX]|\d{13})$/', $isbn) === 1;
    }

    protected function validate(array $data, bool $isUpdate): array
    {
        if (isset($data['isbn']) && is_string($data['isbn'])) {
            $data['isbn'] = self::normalizeIsbn($data['isbn']);
        }

        $v = new Validator($data, $isUpdate);
        $v->string('title', 150, true)
          ->string('isbn', 13)
          ->integer('publication_year', 0, (int) date('Y') + 1)
          ->string('description', 5000)
          ->string('cover_url', 255)
          ->integer('total_copies', 1, 1000)
          ->integer('author_id', 1, PHP_INT_MAX, true)
          ->integer('category_id', 1, PHP_INT_MAX);

        $clean = $v->getData();
        if (!empty($clean['isbn']) && !self::isValidIsbn($clean['isbn'])) {
            $v->addError('isbn', 'isbn must have 10 or 13 digits (ISBN-10 may end with X).');
        }
        if (!empty($clean['cover_url']) && filter_var($clean['cover_url'], FILTER_VALIDATE_URL) === false) {
            $v->addError('cover_url', 'cover_url must be a valid URL.');
        }
        $v->throwIfInvalid();

        // On create, an optional field that was not sent is stored as NULL
        if (!$isUpdate) {
            $clean += ['isbn' => null];
        }
        return $clean;
    }

    /** The author (and category, if given) must exist; otherwise the foreign key would fail */
    private function checkRelations(array $clean): void
    {
        if (isset($clean['author_id']) && $this->authorDao->getById($clean['author_id']) === null) {
            throw new ApiException('Validation failed.', 400, ['author_id' => 'Author does not exist.']);
        }
        if (isset($clean['category_id']) && $this->categoryDao->getById($clean['category_id']) === null) {
            throw new ApiException('Validation failed.', 400, ['category_id' => 'Category does not exist.']);
        }
    }

    private function checkUniqueIsbn(string $isbn, ?int $exceptId = null): void
    {
        $existing = $this->dao->getByIsbn($isbn);
        if ($existing !== null && (int) $existing['id'] !== $exceptId) {
            throw new ApiException('Validation failed.', 400, ['isbn' => "A book with ISBN $isbn already exists: \"{$existing['title']}\"."]);
        }
    }
}
