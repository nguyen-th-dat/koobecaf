<?php

declare(strict_types=1);

namespace App\Core;

final class Validator
{
    /** @var array<string,string> */
    private array $errors = [];

    /** @var array<string,mixed> */
    private array $clean = [];

    public function __construct(private array $data)
    {
    }

    public function required(string $field, string $label): self
    {
        $value = trim((string) ($this->data[$field] ?? ''));
        if ($value === '') {
            $this->addError($field, "{$label} không được để trống.");
        }

        return $this;
    }

    public function email(string $field, string $label = 'Email'): self
    {
        $value = trim((string) ($this->data[$field] ?? ''));
        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, "{$label} không hợp lệ.");
        }

        return $this;
    }

    public function min(string $field, int $length, string $label): self
    {
        $value = (string) ($this->data[$field] ?? '');
        if ($value !== '' && mb_strlen($value) < $length) {
            $this->addError($field, "{$label} phải có ít nhất {$length} ký tự.");
        }

        return $this;
    }

    public function max(string $field, int $length, string $label): self
    {
        $value = (string) ($this->data[$field] ?? '');
        if (mb_strlen($value) > $length) {
            $this->addError($field, "{$label} không được vượt quá {$length} ký tự.");
        }

        return $this;
    }

    public function matches(string $field, string $otherField, string $label): self
    {
        if (($this->data[$field] ?? null) !== ($this->data[$otherField] ?? null)) {
            $this->addError($field, "{$label} không khớp.");
        }

        return $this;
    }

    public function in(string $field, array $allowed, string $label): self
    {
        $value = (string) ($this->data[$field] ?? '');
        if ($value !== '' && !in_array($value, $allowed, true)) {
            $this->addError($field, "{$label} không hợp lệ.");
        }

        return $this;
    }

    public function username(string $field, string $label = 'Username'): self
    {
        $value = trim((string) ($this->data[$field] ?? ''));
        if ($value !== '' && !preg_match('/^[a-zA-Z0-9_]{3,30}$/', $value)) {
            $this->addError($field, "{$label} chỉ gồm chữ, số và dấu gạch dưới, từ 3 đến 30 ký tự.");
        }

        return $this;
    }

    public function addError(string $field, string $message): self
    {
        $this->errors[$field] = $message;

        return $this;
    }

    public function passes(): bool
    {
        return $this->errors === [];
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    /** @return array<string,string> */
    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        return $this->errors === [] ? null : reset($this->errors);
    }
}
