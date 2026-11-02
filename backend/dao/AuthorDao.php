<?php

require_once __DIR__ . '/BaseDao.php';

class AuthorDao extends BaseDao
{
    protected string $table = 'authors';
    protected array $columns = ['first_name', 'last_name', 'nationality', 'birth_year', 'biography'];

    /** Authors with the number of books each one has in the library */
    public function getAllWithBookCount(): array
    {
        return $this->query(
            'SELECT a.*, COUNT(b.id) AS book_count
             FROM authors a
             LEFT JOIN books b ON b.author_id = a.id
             GROUP BY a.id
             ORDER BY a.last_name, a.first_name'
        );
    }

    /** Search by first or last name (used by the search field on the Authors page) */
    public function searchByName(string $term): array
    {
        return $this->query(
            'SELECT * FROM authors
             WHERE first_name LIKE :term1 OR last_name LIKE :term2
             ORDER BY last_name',
            ['term1' => $this->likePattern($term), 'term2' => $this->likePattern($term)]
        );
    }
}
