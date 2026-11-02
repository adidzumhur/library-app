<?php

require_once __DIR__ . '/Database.php';

/**
 * Base class for all DAOs. Contains the reusable CRUD methods.
 *
 * Every child DAO only defines:
 *   - $table   : the database table name
 *   - $columns : the columns that may be inserted/updated
 *
 * All values are sent to MySQL through prepared statements (placeholders like :title),
 * so user input is never concatenated into SQL.
 * Column names cannot be placeholders, so they are checked against the $columns whitelist.
 */
abstract class BaseDao
{
    protected PDO $connection;
    protected string $table;
    protected array $columns = [];

    public function __construct()
    {
        $this->connection = Database::getConnection();
    }

    /** READ - all rows */
    public function getAll(): array
    {
        $stmt = $this->connection->prepare("SELECT * FROM {$this->table} ORDER BY id");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** READ - one row by id, or null if it does not exist */
    public function getById(int $id): ?array
    {
        $stmt = $this->connection->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** CREATE - inserts a row and returns it (with the new id) */
    public function insert(array $data): array
    {
        $data = $this->filterColumns($data);

        $columns      = implode(', ', array_keys($data));                                // title, isbn
        $placeholders = implode(', ', array_map(fn ($c) => ":$c", array_keys($data)));   // :title, :isbn

        $stmt = $this->connection->prepare("INSERT INTO {$this->table} ($columns) VALUES ($placeholders)");
        $stmt->execute($data);

        return $this->getById((int) $this->connection->lastInsertId());
    }

    /** UPDATE - updates only the given columns and returns the updated row (null if not found) */
    public function update(int $id, array $data): ?array
    {
        $data = $this->filterColumns($data);

        $set = implode(', ', array_map(fn ($c) => "$c = :$c", array_keys($data)));       // title = :title

        $stmt = $this->connection->prepare("UPDATE {$this->table} SET $set WHERE id = :id");
        $stmt->execute($data + ['id' => $id]);

        return $this->getById($id);
    }

    /** DELETE - returns true if a row was deleted */
    public function delete(int $id): bool
    {
        $stmt = $this->connection->prepare("DELETE FROM {$this->table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /** Runs a custom SELECT with parameters and returns all rows (used by child DAOs) */
    protected function query(string $sql, array $params = []): array
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Runs a custom SELECT with parameters and returns the first row or null */
    protected function queryOne(string $sql, array $params = []): ?array
    {
        $rows = $this->query($sql, $params);
        return $rows[0] ?? null;
    }

    /**
     * Builds a pattern for "contains" searches with LIKE.
     * % and _ are wildcards in LIKE, so they are escaped with a backslash;
     * otherwise searching for "_" or "%" would match every row.
     */
    protected function likePattern(string $term): string
    {
        return '%' . addcslashes($term, '%_\\') . '%';
    }

    /**
     * Keeps only the allowed columns, so unknown keys (or SQL injection attempts
     * in column names) never reach the query.
     */
    private function filterColumns(array $data): array
    {
        $filtered = array_intersect_key($data, array_flip($this->columns));

        if (empty($filtered)) {
            throw new InvalidArgumentException("No valid columns given for table {$this->table}.");
        }

        return $filtered;
    }
}
