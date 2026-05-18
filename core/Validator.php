<?php

class Validator {
    private array $errors = [];

    public function required(string $field, mixed $value): self {
        if (empty(trim((string)$value))) {
            $this->errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
        }
        return $this;
    }

    public function minLength(string $field, mixed $value, int $min): self {
        if (strlen((string)$value) < $min) {
            $this->errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " must be at least $min characters.";
        }
        return $this;
    }

    public function numeric(string $field, mixed $value): self {
        if (!is_numeric($value)) {
            $this->errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' must be a number.';
        }
        return $this;
    }

    public function positive(string $field, mixed $value): self {
        if ((float)$value <= 0) {
            $this->errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' must be greater than zero.';
        }
        return $this;
    }

    public function sanitize(mixed $value): string {
        return htmlspecialchars(strip_tags(trim((string)$value)), ENT_QUOTES, 'UTF-8');
    }

    public function fails(): bool {
        return !empty($this->errors);
    }

    public function errors(): array {
        return $this->errors;
    }

    public function firstError(): string {
        return array_values($this->errors)[0] ?? '';
    }
}
