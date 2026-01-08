<?php

namespace App\Dto;

class ImportResult
{
    private int $processed = 0;
    private int $success = 0;
    private int $updated = 0;
    private int $skipped = 0;
    private array $errors = [];

    public function incrementProcessed(): void
    {
        $this->processed++;
    }

    public function incrementSuccess(): void
    {
        $this->success++;
    }

    public function incrementUpdated(): void
    {
        $this->updated++;
    }

    public function incrementSkipped(): void
    {
        $this->skipped++;
    }

    public function addError(string $message): void
    {
        $this->errors[] = $message;
    }

    public function getProcessed(): int
    {
        return $this->processed;
    }

    public function getSuccess(): int
    {
        return $this->success;
    }

    public function getUpdated(): int
    {
        return $this->updated;
    }

    public function getSkipped(): int
    {
        return $this->skipped;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}