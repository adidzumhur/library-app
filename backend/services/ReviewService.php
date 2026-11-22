<?php

require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/ReviewDao.php';
require_once __DIR__ . '/../dao/BookDao.php';
require_once __DIR__ . '/../dao/UserDao.php';

class ReviewService extends BaseService
{
    protected string $entityName = 'Review';
    private BookDao $bookDao;
    private UserDao $userDao;

    public function __construct()
    {
        parent::__construct(new ReviewDao());
        $this->bookDao = new BookDao();
        $this->userDao = new UserDao();
    }

    /** All reviews with book title and reviewer name (Reviews page) */
    public function getAll(): array
    {
        return $this->dao->getAllWithDetails();
    }

    /** Reviews of one book (Book details page) */
    public function getByBook(int $bookId): array
    {
        if ($this->bookDao->getById($bookId) === null) {
            throw new ApiException('Book not found.', 404);
        }
        return $this->dao->getByBook($bookId);
    }

    /** A member can review the same book only once (UNIQUE key on user_id + book_id) */
    public function create(array $data): array
    {
        $clean = $this->validate($data, false);

        if ($this->userDao->getById($clean['user_id']) === null) {
            throw new ApiException('Validation failed.', 400, ['user_id' => 'User does not exist.']);
        }
        if ($this->bookDao->getById($clean['book_id']) === null) {
            throw new ApiException('Validation failed.', 400, ['book_id' => 'Book does not exist.']);
        }
        if ($this->dao->getByUserAndBook($clean['user_id'], $clean['book_id']) !== null) {
            throw new ApiException('This member has already reviewed this book. Edit the existing review instead.', 400);
        }
        return $this->dao->insert($clean);
    }

    protected function validate(array $data, bool $isUpdate): array
    {
        $v = new Validator($data, $isUpdate);
        if (!$isUpdate) {
            // The reviewer and the book are chosen once; an update can change only rating and comment
            $v->integer('user_id', 1, PHP_INT_MAX, true)
              ->integer('book_id', 1, PHP_INT_MAX, true);
        }
        $v->integer('rating', 1, 5, true)
          ->string('comment', 1000);
        $v->throwIfInvalid();
        return $v->getData();
    }
}
