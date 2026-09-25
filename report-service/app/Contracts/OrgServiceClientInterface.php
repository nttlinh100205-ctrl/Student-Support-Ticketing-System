<?php

namespace App\Contracts;

interface OrgServiceClientInterface
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getDepartments(): array;

    /**
     * @return array<string, mixed>|null
     */
    public function getDepartmentById(int $departmentId): ?array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getSupportTypes(): array;

    /**
     * @return array<string, mixed>|null
     */
    public function getSupportTypeById(int $supportTypeId): ?array;
}
