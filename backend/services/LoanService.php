<?php

require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/LoanDao.php';
require_once __DIR__ . '/../dao/BookDao.php';
require_once __DIR__ . '/../dao/UserDao.php';

class LoanService extends BaseService
{
    protected string $entityName = 'Loan';
    private const LOAN_DAYS = 14;               // default loan period
    private const STATUSES  = ['borrowed', 'returned', 'overdue'];

    private BookDao $bookDao;
    private UserDao $userDao;

    public function __construct()
    {
        parent::__construct(new LoanDao());
        $this->bookDao = new BookDao();
        $this->userDao = new UserDao();
    }

    /** All loans with book and member names; optional ?status= filter */
    public function getAll(?string $status = null): array
    {
        // Loans whose due date has passed become "overdue" before they are shown
        $this->dao->markOverdue();

        if ($status !== null && $status !== '') {
            if (!in_array($status, self::STATUSES, true)) {
                throw new ApiException('status must be one of: ' . implode(', ', self::STATUSES) . '.', 400);
            }
            return $this->dao->getByStatus($status);
        }
        return $this->dao->getAllWithDetails();
    }

    /** Loans of one member (Profile page - "My loans") */
    public function getByUser(int $userId): array
    {
        if ($this->userDao->getById($userId) === null) {
            throw new ApiException('User not found.', 404);
        }
        $this->dao->markOverdue();
        return $this->dao->getByUser($userId);
    }

    /**
     * Borrowing a book:
     *   1. the member and the book must exist
     *   2. the member must not already have this book borrowed
     *   3. one available copy is taken and the loan is saved
     * Step 3 runs in a transaction: if saving the loan fails, the copy is given back.
     */
    public function create(array $data): array
    {
        $clean = $this->validate($data, false);

        if ($this->userDao->getById($clean['user_id']) === null) {
            throw new ApiException('Validation failed.', 400, ['user_id' => 'User does not exist.']);
        }
        if ($this->bookDao->getById($clean['book_id']) === null) {
            throw new ApiException('Validation failed.', 400, ['book_id' => 'Book does not exist.']);
        }
        if ($this->dao->getActiveLoan($clean['user_id'], $clean['book_id']) !== null) {
            throw new ApiException('This member already has this book borrowed.', 400);
        }

        $clean['loan_date'] = $clean['loan_date'] ?? date('Y-m-d');
        $clean['due_date']  = $clean['due_date'] ?? date('Y-m-d', strtotime($clean['loan_date'] . ' +' . self::LOAN_DAYS . ' days'));
        $this->checkDates($clean['loan_date'], $clean['due_date']);
        $clean['status'] = $this->activeStatus($clean['due_date']);

        $this->dao->beginTransaction();
        try {
            // changeAvailableCopies() returns false when no copy is left (see BookDao)
            if (!$this->bookDao->changeAvailableCopies($clean['book_id'], -1)) {
                throw new ApiException('No copies of this book are available right now.', 400);
            }
            $loan = $this->dao->insert($clean);
            $this->dao->commit();
            return $loan;
        } catch (Throwable $e) {
            $this->dao->rollBack();
            throw $e;
        }
    }

    /**
     * Only the due date can be changed (e.g. extending a loan).
     * Book, member and return are not edited here; returning has its own endpoint.
     */
    public function update(int $id, array $data): array
    {
        $loan = $this->getById($id);

        $clean = $this->validate($data, true);
        if (!isset($clean['due_date'])) {
            throw new ApiException('Validation failed.', 400, ['due_date' => 'due_date is required. Only the due date of a loan can be changed.']);
        }
        $this->checkDates($loan['loan_date'], $clean['due_date']);

        $changes = ['due_date' => $clean['due_date']];
        if ($loan['status'] !== 'returned') {
            $changes['status'] = $this->activeStatus($clean['due_date']); // extended loan is no longer overdue
        }
        return $this->dao->update($id, $changes);
    }

    /** Marks the loan as returned today and gives the copy back to the library */
    public function returnLoan(int $id): array
    {
        $loan = $this->getById($id);
        if ($loan['status'] === 'returned') {
            throw new ApiException('This loan has already been returned.', 400);
        }

        $this->dao->beginTransaction();
        try {
            $updated = $this->dao->update($id, ['return_date' => date('Y-m-d'), 'status' => 'returned']);
            $this->bookDao->changeAvailableCopies((int) $loan['book_id'], +1);
            $this->dao->commit();
            return $updated;
        } catch (Throwable $e) {
            $this->dao->rollBack();
            throw $e;
        }
    }

    /** Deleting a loan that was not returned also gives the copy back */
    public function delete(int $id): void
    {
        $loan = $this->getById($id);

        $this->dao->beginTransaction();
        try {
            $this->dao->delete($id);
            if ($loan['status'] !== 'returned') {
                $this->bookDao->changeAvailableCopies((int) $loan['book_id'], +1);
            }
            $this->dao->commit();
        } catch (Throwable $e) {
            $this->dao->rollBack();
            throw $e;
        }
    }

    protected function validate(array $data, bool $isUpdate): array
    {
        $v = new Validator($data, $isUpdate);
        if (!$isUpdate) {
            $v->integer('user_id', 1, PHP_INT_MAX, true)
              ->integer('book_id', 1, PHP_INT_MAX, true)
              ->date('loan_date');
        }
        $v->date('due_date');
        $v->throwIfInvalid();
        return array_filter($v->getData(), fn ($value) => $value !== null); // empty dates use the defaults
    }

    private function checkDates(string $loanDate, string $dueDate): void
    {
        if ($dueDate < $loanDate) { // YYYY-MM-DD strings compare correctly as text
            throw new ApiException('Validation failed.', 400, ['due_date' => 'due_date cannot be before loan_date.']);
        }
    }

    /** A loan that is not returned is "overdue" if the due date has passed, otherwise "borrowed" */
    private function activeStatus(string $dueDate): string
    {
        return $dueDate < date('Y-m-d') ? 'overdue' : 'borrowed';
    }
}
