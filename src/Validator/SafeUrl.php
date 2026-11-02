<?php

namespace App\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute(Attribute::TARGET_PROPERTY)]
class SafeUrl extends Constraint
{
    public string $message = 'The provided URL is not allowed (SSRF guard).';

    public function validatedBy(): string
    {
        return SafeUrlValidator::class;
    }
}
