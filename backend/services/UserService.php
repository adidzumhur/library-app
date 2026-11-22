<?php

require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/UserDao.php';

/**
 * Users are managed by the admin (Users page).
 * Registration and login with JWT are added in Milestone 4 and will reuse create().
 *
 * The password hash is NEVER returned to the client.
 */
class UserService extends BaseService
{
    protected string $entityName = 'User';

    public function __construct()
    {
        parent::__construct(new UserDao());
    }

    public function getAll(): array
    {
        return $this->dao->getAllPublic();
    }

    public function getById(int $id): array
    {
        return $this->withoutPassword(parent::getById($id));
    }

    public function create(array $data): array
    {
        $clean = $this->validate($data, false);
        $this->checkUniqueEmail($clean['email']);
        $clean['role'] = $clean['role'] ?? 'member';

        return $this->withoutPassword($this->dao->insert($clean));
    }

    public function update(int $id, array $data): array
    {
        parent::getById($id);

        $clean = $this->validate($data, true);
        if (empty($clean)) {
            throw new ApiException('No valid fields to update.', 400);
        }
        if (isset($clean['email'])) {
            $this->checkUniqueEmail($clean['email'], $id);
        }
        return $this->withoutPassword($this->dao->update($id, $clean));
    }

    /**
     * The client sends "password"; the database stores only "password_hash".
     * password_hash() with bcrypt adds a random salt, so the same password gives a different hash every time.
     */
    protected function validate(array $data, bool $isUpdate): array
    {
        $v = new Validator($data, $isUpdate);
        $v->string('first_name', 50, true)
          ->string('last_name', 50, true)
          ->email('email', true)
          ->string('password', 72, true, 6) // bcrypt uses only the first 72 bytes
          ->in('role', ['admin', 'member']);
        $v->throwIfInvalid();

        $clean = $v->getData();
        if (isset($clean['email'])) {
            $clean['email'] = strtolower($clean['email']);
        }
        if (isset($clean['password'])) {
            $clean['password_hash'] = password_hash($clean['password'], PASSWORD_BCRYPT);
            unset($clean['password']);
        }
        return $clean;
    }

    private function checkUniqueEmail(string $email, ?int $exceptId = null): void
    {
        $existing = $this->dao->getByEmail($email);
        if ($existing !== null && (int) $existing['id'] !== $exceptId) {
            throw new ApiException('Validation failed.', 400, ['email' => 'This email is already registered.']);
        }
    }

    private function withoutPassword(array $user): array
    {
        unset($user['password_hash']);
        return $user;
    }
}
