<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class ContactRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 100)]
        public string $name,

        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email,

        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 5000)]
        public string $message,

        #[Assert\Choice(choices: ['en', 'uk'])]
        public string $locale,

        #[Assert\Blank]
        public string $honeypot,
    ) {}
}
