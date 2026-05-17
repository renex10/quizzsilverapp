<?php

namespace App\Services;

class ValidationResult
{
    private bool $valid;
    private array $errors;
    private string $layer; // syntax, type_validation, logical

    public function __construct(bool $valid, array $errors, string $layer)
    {
        $this->valid = $valid;
        $this->errors = $errors;
        $this->layer = $layer;
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getLayer(): string
    {
        return $this->layer;
    }
}