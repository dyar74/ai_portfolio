<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Validator\SafeUrl;
use App\Validator\SafeUrlValidator;
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;
use PHPUnit\Framework\TestCase;

class SafeUrlValidatorTest extends TestCase
{
    private SafeUrlValidator $validator;
    private ExecutionContextInterface $context;

    protected function setUp(): void
    {
        $this->validator = new SafeUrlValidator();
        $this->context = $this->createMock(ExecutionContextInterface::class);
    }

    public function testPublicUrlPasses(): void
    {
        $constraint = new SafeUrl();
        // 8.8.8.8 is a public IP
        $this->context->method('getPropertyPath')->willReturn('url');
        $violation = $this->createMock(ConstraintViolationBuilderInterface::class);

        // The validator should NOT add a violation for a public IP
        // We test this by checking that buildViolation is never called
        $this->context->expects($this->never())->method('buildViolation');

        $this->validator->validate('http://8.8.8.8/page', $constraint);
    }

    public function testLoopbackIpBlocked(): void
    {
        $this->context->method('getPropertyPath')->willReturn('url');

        $this->context->expects($this->once())->method('buildViolation')->willReturn($this->createMock(ConstraintViolationBuilderInterface::class));

        $this->validator->validate('http://127.0.0.1/page', new SafeUrl());
    }

    public function testPrivate10RangeBlocked(): void
    {
        $this->context->method('getPropertyPath')->willReturn('url');

        $this->context->expects($this->once())->method('buildViolation')->willReturn($this->createMock(ConstraintViolationBuilderInterface::class));

        $this->validator->validate('http://10.0.0.1/page', new SafeUrl());
    }

    public function testPrivate172RangeBlocked(): void
    {
        $this->context->method('getPropertyPath')->willReturn('url');

        $this->context->expects($this->once())->method('buildViolation')->willReturn($this->createMock(ConstraintViolationBuilderInterface::class));

        $this->validator->validate('http://172.16.0.1/page', new SafeUrl());
    }

    public function testPrivate192RangeBlocked(): void
    {
        $this->context->method('getPropertyPath')->willReturn('url');

        $this->context->expects($this->once())->method('buildViolation')->willReturn($this->createMock(ConstraintViolationBuilderInterface::class));

        $this->validator->validate('http://192.168.1.1/page', new SafeUrl());
    }

    public function testLinkLocalBlocked(): void
    {
        $this->context->method('getPropertyPath')->willReturn('url');

        $this->context->expects($this->once())->method('buildViolation')->willReturn($this->createMock(ConstraintViolationBuilderInterface::class));

        $this->validator->validate('http://169.254.1.1/page', new SafeUrl());
    }

    public function testZeroRangeBlocked(): void
    {
        $this->context->method('getPropertyPath')->willReturn('url');

        $this->context->expects($this->once())->method('buildViolation')->willReturn($this->createMock(ConstraintViolationBuilderInterface::class));

        $this->validator->validate('http://0.0.0.0/page', new SafeUrl());
    }

    public function testCgnatRangeBlocked(): void
    {
        $this->context->method('getPropertyPath')->willReturn('url');

        $this->context->expects($this->once())->method('buildViolation')->willReturn($this->createMock(ConstraintViolationBuilderInterface::class));

        $this->validator->validate('http://100.64.0.1/page', new SafeUrl());
    }

    public function testNonHttpSchemeBlocked(): void
    {
        $this->context->method('getPropertyPath')->willReturn('url');

        $this->context->expects($this->once())->method('buildViolation')->willReturn($this->createMock(ConstraintViolationBuilderInterface::class));

        $this->validator->validate('ftp://example.com/file', new SafeUrl());
    }

    public function testMalformedUrlBlocked(): void
    {
        $this->context->method('getPropertyPath')->willReturn('url');

        $this->context->expects($this->once())->method('buildViolation')->willReturn($this->createMock(ConstraintViolationBuilderInterface::class));

        $this->validator->validate('://invalid', new SafeUrl());
    }

    public function testEmptyStringPasses(): void
    {
        // Empty string is allowed (the NotBlank constraint handles that)
        $this->context->expects($this->never())->method('buildViolation');

        $this->validator->validate('', new SafeUrl());
    }
}
