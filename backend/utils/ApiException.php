<?php

/**
 * An error that should be sent to the client with a specific HTTP status code.
 *
 * Services throw it, for example:
 *   throw new ApiException('Book not found.', 404);
 *   throw new ApiException('Validation failed.', 400, ['title' => 'Title is required.']);
 *
 * The error handler in index.php turns it into a JSON response.
 * Its message is always safe to show to the user (no SQL, paths or stack traces).
 */
class ApiException extends Exception
{
    private array $errors;

    public function __construct(string $message, int $statusCode = 400, array $errors = [])
    {
        parent::__construct($message, $statusCode);
        $this->errors = $errors;
    }

    public function getStatusCode(): int
    {
        return $this->getCode();
    }

    /** Field-level validation errors, e.g. ['email' => 'Email is not valid.'] */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
