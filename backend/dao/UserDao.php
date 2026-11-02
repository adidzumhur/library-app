<?php

require_once __DIR__ . '/BaseDao.php';

class UserDao extends BaseDao
{
    protected string $table = 'users';
    protected array $columns = ['first_name', 'last_name', 'email', 'password_hash', 'role'];

    /** Used for login and for checking if an email is already registered */
    public function getByEmail(string $email): ?array
    {
        return $this->queryOne('SELECT * FROM users WHERE email = :email', ['email' => $email]);
    }

    /** All users without the password hash (safe to return to the frontend) */
    public function getAllPublic(): array
    {
        return $this->query(
            'SELECT id, first_name, last_name, email, role, created_at FROM users ORDER BY id'
        );
    }
}
