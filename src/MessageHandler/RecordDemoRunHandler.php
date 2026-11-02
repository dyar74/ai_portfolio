<?php

namespace App\MessageHandler;

use App\Entity\DemoRun;
use App\Message\RecordDemoRunMessage;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class RecordDemoRunHandler
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(RecordDemoRunMessage $message): void
    {
        $run = (new DemoRun())
            ->setSlug($message->slug)
            ->setStatus($message->status)
            ->setDurationMs($message->durationMs)
            ->setLocale($message->locale)
            ->setRequestId($message->requestId);

        $this->em->persist($run);
        $this->em->flush();

        // Never log payloads; only metadata for correlation.
        $this->logger->info('demo_run recorded', [
            'slug' => $message->slug,
            'status' => $message->status,
            'durationMs' => $message->durationMs,
            'requestId' => $message->requestId,
        ]);
    }
}
