<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class TranslateRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 1000)]
        public string $text,

        #[Assert\Choice(choices: ['en-uk', 'uk-en'])]
        public string $direction,

        #[Assert\Choice(choices: ['neutral', 'formal', 'casual'])]
        public string $tone,
    ) {}
}
