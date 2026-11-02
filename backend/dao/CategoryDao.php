<?php

require_once __DIR__ . '/BaseDao.php';

class CategoryDao extends BaseDao
{
    protected string $table = 'categories';
    protected array $columns = ['name', 'description'];

    /** Used to prevent duplicate category names */
    public function getByName(string $name): ?array
    {
        return $this->queryOne('SELECT * FROM categories WHERE name = :name', ['name' => $name]);
    }

    /** Categories with the number of books in each (dashboard and Categories page) */
    public function getAllWithBookCount(): array
    {
        return $this->query(
            'SELECT c.*, COUNT(b.id) AS book_count
             FROM categories c
             LEFT JOIN books b ON b.category_id = c.id
             GROUP BY c.id
             ORDER BY book_count DESC, c.name'
        );
    }
}
