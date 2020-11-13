<?php
declare(strict_types=1);

namespace Markup\JobQueueBundle\Factory;

use Markup\JobQueueBundle\Entity\JobStatus;
use Markup\JobQueueBundle\Repository\JobStatusRepository;

class JobStatusFactory
{
    /** @var JobStatusRepository */
    private $jobStatusRepository;

    public function __construct(JobStatusRepository $jobStatusRepository)
    {
        $this->jobStatusRepository = $jobStatusRepository;
    }

    public function isStatusEnabled(string $command, ?string $arguments): bool
    {
        $jobStatus = $this->jobStatusRepository->findBy([
            'command' => $command,
            'arguments' => $arguments ?? '[]'
        ]);

        if (!$jobStatus) {
            return true;
        }

        return $jobStatus->getEnabled();
    }

    public function isUserEnabledJob(string $command, array $arguments): bool
    {
        return $this->jobStatusRepository->isUserEnabled($command, $arguments);
    }

    public function fetchOrCreateJobStatus(string $command, string $arguments): JobStatus
    {
        $jobStatus = $this->jobStatusRepository->findBy([
            'command' => $command,
            'arguments' => ($arguments) ?? null
        ]);

        return $jobStatus ?? new JobStatus(null, $command, $arguments, true);
    }

    public function saveJobStatus(JobStatus $jobStatus): void
    {
        $this->jobStatusRepository->store($jobStatus);
    }
}
