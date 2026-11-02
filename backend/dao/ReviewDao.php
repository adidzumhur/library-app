<?php

require_once __DIR__ . '/BaseDao.php';

class ReviewDao extends BaseDao
{
    protected string $table = 'reviews';
    protected array $columns = ['user_id', 'book_id', 'rating', 'comment'];

    // Shared SELECT that adds the book title and reviewer name to each review
    private const SELECT_WITH_DETAILS =
        "SELECT r.*,
                bk.title AS book_title,
                CONCAT(u.first_name, ' ', u.last_name) AS user_name
         FROM reviews r
         JOIN books bk ON bk.id = r.book_id
         JOIN users u  ON u.id  = r.user_id";

    /** All reviews with book and user names (Reviews page) */
    public function getAllWithDetails(): array
    {
        return $this->query(self::SELECT_WITH_DETAILS . ' ORDER BY r.created_at DESC');
    }

    /** Reviews of one book (Book details page) */
    public function getByBook(int $bookId): array
    {
        return $this->query(
            self::SELECT_WITH_DETAILS . ' WHERE r.book_id = :book_id ORDER BY r.created_at DESC',
            ['book_id' => $bookId]
        );
    }

    /** Average rating and number of reviews for a book, e.g. ['average' => 4.5, 'count' => 2] */
    public function getRatingSummary(int $bookId): array
    {
        $row = $this->queryOne(
            'SELECT ROUND(AVG(rating), 1) AS average, COUNT(*) AS count
             FROM reviews WHERE book_id = :book_id',
            ['book_id' => $bookId]
        );
        return ['average' => $row['average'] !== null ? (float) $row['average'] : null, 'count' => (int) $row['count']];
    }

    /** Used to prevent the same user reviewing the same book twice */
    public function getByUserAndBook(int $userId, int $bookId): ?array
    {
        return $this->queryOne(
            'SELECT * FROM reviews WHERE user_id = :user_id AND book_id = :book_id',
            ['user_id' => $userId, 'book_id' => $bookId]
        );
    }
}
