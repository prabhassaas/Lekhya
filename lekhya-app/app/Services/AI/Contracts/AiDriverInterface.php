<?php
namespace App\Services\AI\Contracts;

interface AiDriverInterface
{
    public function extractInvoice(string $text, ?string $imageBase64 = null): array;
    public function parseNlQueryIntent(string $query): array;
    public function suggestAccount(string $description, float $amount, string $vendor): array;
    public function detectAnomaly(array $journalData, float $averageAmount): array;
    public function isAvailable(): bool;
}
