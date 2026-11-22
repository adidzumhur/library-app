<?php

require_once __DIR__ . '/ApiException.php';

/**
 * Validates request data and collects all errors, so the user sees every problem at once.
 *
 * Usage in a service:
 *   $v = new Validator($data, $isUpdate);
 *   $v->string('title', 150, true);
 *   $v->integer('publication_year', 0, 2100);
 *   $v->throwIfInvalid();          // 400 with the list of errors
 *   $clean = $v->getData();        // only the validated fields
 *
 * Create (POST): required fields must be present.
 * Update (PUT):  only the fields that were sent are validated and changed,
 *                but a required field that is sent cannot be empty.
 */
class Validator
{
    private array $input;
    private bool $isUpdate;
    private array $clean = [];
    private array $errors = [];

    public function __construct(array $input, bool $isUpdate = false)
    {
        $this->input = $input;
        $this->isUpdate = $isUpdate;
    }

    /** Text field: trimmed, with a maximum length. An empty optional field is stored as NULL. */
    public function string(string $field, int $maxLength, bool $required = false, int $minLength = 0): self
    {
        if (!$this->shouldCheck($field, $required)) {
            return $this;
        }

        $value = $this->input[$field];
        if ($value !== null && !is_scalar($value)) {
            return $this->addError($field, "$field must be text.");
        }
        $value = trim((string) $value);

        if ($value === '') {
            return $required ? $this->addError($field, "$field is required.") : $this->set($field, null);
        }
        if (mb_strlen($value) < $minLength) {
            return $this->addError($field, "$field must have at least $minLength characters.");
        }
        if (mb_strlen($value) > $maxLength) {
            return $this->addError($field, "$field can have at most $maxLength characters.");
        }
        return $this->set($field, $value);
    }

    /** Whole number between $min and $max. Accepts 5 or "5", rejects "5.5" or "abc". */
    public function integer(string $field, int $min, int $max, bool $required = false): self
    {
        if (!$this->shouldCheck($field, $required)) {
            return $this;
        }

        $value = $this->input[$field];
        if ($value === null || $value === '') {
            return $required ? $this->addError($field, "$field is required.") : $this->set($field, null);
        }

        $number = filter_var($value, FILTER_VALIDATE_INT);
        if ($number === false || is_bool($value)) {
            return $this->addError($field, "$field must be a whole number.");
        }
        if ($number < $min || $number > $max) {
            return $this->addError($field, "$field must be between $min and $max.");
        }
        return $this->set($field, $number);
    }

    public function email(string $field, bool $required = false): self
    {
        $this->string($field, 100, $required);
        $value = $this->clean[$field] ?? null;
        if ($value !== null && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            unset($this->clean[$field]);
            $this->addError($field, "$field is not a valid email address.");
        }
        return $this;
    }

    /** Value must be one of the allowed options, e.g. role: admin or member */
    public function in(string $field, array $allowed, bool $required = false): self
    {
        if (!$this->shouldCheck($field, $required)) {
            return $this;
        }

        $value = $this->input[$field];
        if (!in_array($value, $allowed, true)) {
            return $this->addError($field, "$field must be one of: " . implode(', ', $allowed) . '.');
        }
        return $this->set($field, $value);
    }

    /** Date in the format YYYY-MM-DD (e.g. 2026-11-22) */
    public function date(string $field, bool $required = false): self
    {
        if (!$this->shouldCheck($field, $required)) {
            return $this;
        }

        $value = $this->input[$field];
        if ($value === null || $value === '') {
            return $required ? $this->addError($field, "$field is required.") : $this->set($field, null);
        }

        // createFromFormat accepts "2026-02-31" and turns it into March 3rd,
        // so the date is formatted back and compared to catch invalid days.
        $date = is_string($value) ? DateTime::createFromFormat('Y-m-d', $value) : false;
        if ($date === false || $date->format('Y-m-d') !== $value) {
            return $this->addError($field, "$field must be a valid date in the format YYYY-MM-DD.");
        }
        return $this->set($field, $value);
    }

    public function addError(string $field, string $message): self
    {
        $this->errors[$field] = $message;
        return $this;
    }

    public function throwIfInvalid(): void
    {
        if (!empty($this->errors)) {
            throw new ApiException('Validation failed.', 400, $this->errors);
        }
    }

    public function getData(): array
    {
        return $this->clean;
    }

    /**
     * Decides whether a field needs to be checked:
     * - field was sent: always check it
     * - field missing on create and required: error
     * - field missing otherwise: skip it
     */
    private function shouldCheck(string $field, bool $required): bool
    {
        if (array_key_exists($field, $this->input)) {
            return true;
        }
        if ($required && !$this->isUpdate) {
            $this->addError($field, "$field is required.");
        }
        return false;
    }

    private function set(string $field, $value): self
    {
        $this->clean[$field] = $value;
        return $this;
    }
}
