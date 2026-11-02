<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * SSRF guard: only allow http(s) to public hosts.
 * Resolves DNS and blocks private / loopback / link-local / reserved ranges,
 * re-checking every resolved address.
 */
class SafeUrlValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof SafeUrl) {
            return;
        }

        if (!is_string($value) || $value === '') {
            return;
        }

        $url = parse_url($value);
        if ($url === false) {
            $this->violation($constraint->message);
            return;
        }

        $scheme = strtolower($url['scheme'] ?? '');
        if (!in_array($scheme, ['http', 'https'], true)) {
            $this->violation($constraint->message);
            return;
        }

        $host = $url['host'] ?? '';
        if ($host === '') {
            $this->violation($constraint->message);
            return;
        }

        // If the host is a literal IP, check it directly
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            if (!$this->isPublicIp($host)) {
                $this->violation($constraint->message);
            }
            return;
        }

        // Resolve DNS and check every resolved IP
        $records = @gethostbynamel($host);
        if ($records === false) {
            $ip = gethostbyname($host);
            $records = ($ip !== $host) ? [$ip] : [];
        }

        if (empty($records)) {
            $this->violation($constraint->message);
            return;
        }

        foreach ($records as $ip) {
            if (!$this->isPublicIp($ip)) {
                $this->violation($constraint->message);
                return;
            }
        }
    }

    private function isPublicIp(string $ip): bool
    {
        $long = ip2long($ip);
        if ($long === false) {
            return false;
        }

        // Loopback 127.0.0.0/8
        if (($long & 0xFF000000) === 0x7F000000) {
            return false;
        }
        // Private 10.0.0.0/8
        if (($long & 0xFF000000) === 0x0A000000) {
            return false;
        }
        // Private 172.16.0.0/12
        if (($long & 0xFFF00000) === 0xAC100000) {
            return false;
        }
        // Private 192.168.0.0/16
        if (($long & 0xFFFF0000) === 0xC0A80000) {
            return false;
        }
        // Link-local 169.254.0.0/16
        if (($long & 0xFFFF0000) === 0xA9FE0000) {
            return false;
        }
        // 0.0.0.0/8
        if (($long & 0xFF000000) === 0x00000000) {
            return false;
        }
        // 100.64.0.0/10 (CGNAT)
        if (($long & 0xFFC00000) === 0x64400000) {
            return false;
        }

        return true;
    }

    private function violation(string $message): void
    {
        $this->context->buildViolation($message)
            ->atPath((string) $this->context->getPropertyPath())
            ->addViolation();
    }
}
