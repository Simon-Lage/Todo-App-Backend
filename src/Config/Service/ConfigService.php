<?php

declare(strict_types=1);

namespace App\Config\Service;

use App\Exception\ApiProblemException;

final class ConfigService
{
    /**
     * @param string[] $allowedEmailDomains
     */
    public function __construct(private readonly array $allowedEmailDomains)
    {
    }

    /**
     * @return string[]
     */
    public function allowedEmailDomains(): array
    {
        return array_values(array_unique(array_map('strtolower', $this->allowedEmailDomains)));
    }

    public function assertCompanyEmail(string $email): void
    {
        $email = strtolower(trim($email));
        if (!str_contains($email, '@')) {
            throw ApiProblemException::fromStatus(422, 'Unprocessable Entity', 'Email must be a company email.', 'EMAIL_ISNT_COMPANY_EMAIL');
        }

        $parts = explode('@', $email, 2);
        $domain = strtolower($parts[1] ?? '');

        if ($domain === '') {
            throw ApiProblemException::fromStatus(422, 'Unprocessable Entity', 'Email must be a company email.', 'EMAIL_ISNT_COMPANY_EMAIL');
        }

        $allowed = $this->allowedEmailDomains();

        if ($allowed === [] || !in_array($domain, $allowed, true)) {
            throw ApiProblemException::fromStatus(422, 'Unprocessable Entity', 'Email must be a company email.', 'EMAIL_ISNT_COMPANY_EMAIL');
        }
    }
}
