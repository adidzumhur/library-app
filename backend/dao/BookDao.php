<?php

require_once __DIR__ . '/BaseDao.php';

class BookDao extends BaseDao
{
    protected string $table = 'books';
    protected array $columns = [
        'title', 'isbn', 'publication_year', 'description', 'cover_url',
        'total_copies', 'available_copies', 'author_id', 'category_id',
    ];

    // Shared SELECT that adds the author name and category name to each book
    private const SELECT_WITH_DETAILS =
        "SELECT b.*,
                CONCAT(a.first_name, ' ', a.last_name) AS author_name,
                c.name AS category_name
         FROM books b
         JOIN authors a ON a.id = b.author_id
         LEFT JOIN categories c ON c.id = b.category_id";

    /** All books with author and category names (Books page) */
    public function getAllWithDetails(): array
    {
        return $this->query(self::SELECT_WITH_DETAILS . ' ORDER BY b.title');
    }

    /** One book with author and category names (Book details page) */
    public function getByIdWithDetails(int $id): ?array
    {
        return $this->queryOne(self::SELECT_WITH_DETAILS . ' WHERE b.id = :id', ['id' => $id]);
    }

    /** Used to prevent importing the same ISBN twice (Open Library import) */
    public function getByIsbn(string $isbn): ?array
    {
        return $this->queryOne('SELECT * FROM books WHERE isbn = :isbn', ['isbn' => $isbn]);
    }

    public function getByCategory(int $categoryId): array
    {
        return $this->query(
            self::SELECT_WITH_DETAILS . ' WHERE b.category_id = :category_id ORDER BY b.title',
            ['category_id' => $categoryId]
        );
    }

    public function getByAuthor(int $authorId): array
    {
        return $this->query(
            self::SELECT_WITH_DETAILS . ' WHERE b.author_id = :author_id ORDER BY b.publication_year',
            ['author_id' => $authorId]
        );
    }

    /** Search by title or author name */
    public function search(string $term): array
    {
        return $this->query(
            self::SELECT_WITH_DETAILS . "
             WHERE b.title LIKE :term1 OR CONCAT(a.first_name, ' ', a.last_name) LIKE :term2
             ORDER BY b.title",
            ['term1' => $this->likePattern($term), 'term2' => $this->likePattern($term)]
        );
    }

    /**
     * Changes the number of available copies by $change (-1 when borrowed, +1 when returned).
     * The WHERE conditions make sure the value never goes below 0 or above total_copies.
     * Returns false if the change was not possible (e.g. no copies left).
     */
    public function changeAvailableCopies(int $bookId, int $change): bool
    {
        $stmt = $this->connection->prepare(
            'UPDATE books
             SET available_copies = available_copies + :change1
             WHERE id = :id
               AND available_copies + :change2 >= 0
               AND available_copies + :change3 <= total_copies'
        );
        $stmt->execute(['change1' => $change, 'change2' => $change, 'change3' => $change, 'id' => $bookId]);
        return $stmt->rowCount() > 0;
    }
}
