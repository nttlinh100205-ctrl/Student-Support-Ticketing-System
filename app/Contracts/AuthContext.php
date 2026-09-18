<?php

namespace App\Contracts;

interface AuthContext
{
    public function userId(): ?int;
    public function role(): ?string;
    public function departmentId(): ?int;
}