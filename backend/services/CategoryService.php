<?php

require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/CategoryDao.php';
require_once __DIR__ . '/../dao/BookDao.php';

class CategoryService extends BaseService
{
    protected string $entityName = 'Category';
    private BookDao $bookDao;

    public function __construct()
    {
        parent::__construct(new CategoryDao());
        $this->bookDao = new BookDao();
    }

    /** All categories with the number of books in each */
    public function getAll(): array
    {
        return $this->dao->getAllWithBookCount();
    }

    public function getBooks(int $categoryId): array
    {
        $this->getById($categoryId);
        return $this->bookDao->getByCategory($categoryId);
    }

    public function create(array $data): array
    {
        $clean = $this->validate($data, false);
        $this->checkUniqueName($clean['name']);
        return $this->dao->insert($clean);
    }

    public function update(int $id, array $data): array
    {
        $this->getById($id);
        $clean = $this->validate($data, true);
        if (empty($clean)) {
            throw new ApiException('No valid fields to update.', 400);
        }
        if (isset($clean['name'])) {
            $this->checkUniqueName($clean['name'], $id);
        }
        return $this->dao->update($id, $clean);
    }

    // Deleting a category keeps its books: the database sets books.category_id to NULL (ON DELETE SET NULL).

    protected function validate(array $data, bool $isUpdate): array
    {
        $v = new Validator($data, $isUpdate);
        $v->string('name', 50, true)
          ->string('description', 255);
        $v->throwIfInvalid();
        return $v->getData();
    }

    /** Category names are UNIQUE in the database; $exceptId lets a category keep its own name on update */
    private function checkUniqueName(string $name, ?int $exceptId = null): void
    {
        $existing = $this->dao->getByName($name);
        if ($existing !== null && (int) $existing['id'] !== $exceptId) {
            throw new ApiException('Validation failed.', 400, ['name' => "A category named \"$name\" already exists."]);
        }
    }
}
