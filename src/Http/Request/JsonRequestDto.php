<?php

declare(strict_types=1);

namespace App\Http\Request;

interface JsonRequestDto
{
    public static function fromArray(array $payload): static;
}
