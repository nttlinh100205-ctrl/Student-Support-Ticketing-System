<?php

namespace App\Contracts;

interface RequestServiceClientInterface
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function getRequests(array $filters = []): array;

    /**
     * @return array<string, mixed>|null
     */
    public function getRequestById(int $id): ?array;
}
