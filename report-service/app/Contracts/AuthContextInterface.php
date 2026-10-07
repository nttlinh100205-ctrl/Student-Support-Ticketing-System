<?php

namespace App\Contracts;

interface AuthContextInterface
{
    public function userId(): ?int;

    public function role(): ?string;

    public function departmentId(): ?int;
}
