<?php

require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/AuthorDao.php';
require_once __DIR__ . '/../dao/BookDao.php';

class AuthorService extends BaseService
{
    protected string $entityName = 'Author';
    private BookDao $bookDao;

    public function __construct()
    {
        parent::__construct(new AuthorDao());
        $this->bookDao = new BookDao();
    }

    /** All authors with their number of books; with $search only matching names */
    public function getAll(?string $search = null): array
    {
        if ($search !== null && trim($search) !== '') {
            return $this->dao->searchByName(trim($search));
        }
        return $this->dao->getAllWithBookCount();
    }

    /** Books written by one author (Author details / filter on the Books page) */
    public function getBooks(int $authorId): array
    {
        $this->getById($authorId); // 404 if the author does not exist
        return $this->bookDao->getByAuthor($authorId);
    }

    /**
     * The database does not allow deleting an author who still has books (ON DELETE RESTRICT).
     * Checking it here gives the user a clear message instead of a database error.
     */
    public function delete(int $id): void
    {
        $this->getById($id);

        $bookCount = count($this->bookDao->getByAuthor($id));
        if ($bookCount > 0) {
            throw new ApiException("This author still has $bookCount book(s) in the library. Delete or reassign the books first.", 400);
        }
        parent::delete($id);
    }

    protected function validate(array $data, bool $isUpdate): array
    {
        $v = new Validator($data, $isUpdate);
        $v->string('first_name', 50, true)
          ->string('last_name', 50, true)
          ->string('nationality', 50)
          ->integer('birth_year', 0, (int) date('Y'))
          ->string('biography', 5000);
        $v->throwIfInvalid();
        return $v->getData();
    }
}
