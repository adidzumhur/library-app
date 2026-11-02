<?php

require_once __DIR__ . '/BaseDao.php';

class LoanDao extends BaseDao
{
    protected string $table = 'loans';
    protected array $columns = ['user_id', 'book_id', 'loan_date', 'due_date', 'return_date', 'status'];

    // Shared SELECT that adds the book title and member name to each loan
    private const SELECT_WITH_DETAILS =
        "SELECT l.*,
                bk.title AS book_title,
                CONCAT(u.first_name, ' ', u.last_name) AS user_name
         FROM loans l
         JOIN books bk ON bk.id = l.book_id
         JOIN users u  ON u.id  = l.user_id";

    /** All loans with book and member names (Loans page) */
    public function getAllWithDetails(): array
    {
        return $this->query(self::SELECT_WITH_DETAILS . ' ORDER BY l.loan_date DESC');
    }

    /** Loans of one user (Profile page - "My loans") */
    public function getByUser(int $userId): array
    {
        return $this->query(
            self::SELECT_WITH_DETAILS . ' WHERE l.user_id = :user_id ORDER BY l.loan_date DESC',
            ['user_id' => $userId]
        );
    }

    /** Loans filtered by status: borrowed, returned or overdue (status filter on the Loans page) */
    public function getByStatus(string $status): array
    {
        return $this->query(
            self::SELECT_WITH_DETAILS . ' WHERE l.status = :status ORDER BY l.due_date',
            ['status' => $status]
        );
    }

    /** Checks if the user already has this book borrowed (not returned yet) */
    public function getActiveLoan(int $userId, int $bookId): ?array
    {
        return $this->queryOne(
            "SELECT * FROM loans
             WHERE user_id = :user_id AND book_id = :book_id AND status <> 'returned'",
            ['user_id' => $userId, 'book_id' => $bookId]
        );
    }

    /** Marks borrowed loans whose due date has passed as overdue; returns how many were changed */
    public function markOverdue(): int
    {
        $stmt = $this->connection->prepare(
            "UPDATE loans SET status = 'overdue'
             WHERE status = 'borrowed' AND due_date < CURDATE()"
        );
        $stmt->execute();
        return $stmt->rowCount();
    }
}
