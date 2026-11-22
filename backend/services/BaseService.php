<?php

require_once __DIR__ . '/../utils/ApiException.php';
require_once __DIR__ . '/../utils/Validator.php';

/**
 * Base class for all services: the business logic layer.
 *
 * Layer order: route -> service -> DAO -> database.
 * Only services call DAOs. Services validate the data, apply the business rules
 * and throw ApiException with the right HTTP status code when something is wrong.
 *
 * Each child service:
 *   - passes its DAO to the constructor
 *   - sets $entityName (used in messages like "Book not found.")
 *   - implements validate(), which returns only the clean, allowed fields
 *   - overrides a method when the entity needs extra rules (e.g. LoanService::create)
 */
abstract class BaseService
{
    protected BaseDao $dao;
    protected string $entityName = 'Record';

    public function __construct(BaseDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * Checks the input and returns the clean data.
     * $isUpdate = true for PUT, where only the sent fields are validated.
     */
    abstract protected function validate(array $data, bool $isUpdate): array;

    public function getAll(): array
    {
        return $this->dao->getAll();
    }

    /** Returns the row or throws 404 */
    public function getById(int $id): array
    {
        $row = $this->dao->getById($id);
        if ($row === null) {
            throw new ApiException("{$this->entityName} not found.", 404);
        }
        return $row;
    }

    public function create(array $data): array
    {
        $clean = $this->validate($data, false);
        return $this->dao->insert($clean);
    }

    public function update(int $id, array $data): array
    {
        $this->getById($id); // 404 if it does not exist

        $clean = $this->validate($data, true);
        if (empty($clean)) {
            throw new ApiException('No valid fields to update.', 400);
        }
        return $this->dao->update($id, $clean);
    }

    public function delete(int $id): void
    {
        if (!$this->dao->delete($id)) {
            throw new ApiException("{$this->entityName} not found.", 404);
        }
    }
}
