<?php

namespace App\Dto;

use App\Validator\SafeUrl;
use Symfony\Component\Validator\Constraints as Assert;

class SummarizerRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Url]
        #[Assert\Length(max: 2048)]
        #[Assert\Valid]
        #[SafeUrl]
        public string $url,
    ) {}
}
