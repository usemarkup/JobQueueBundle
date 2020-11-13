<?php
declare(strict_types=1);

namespace Markup\JobQueueBundle\Repository;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Markup\JobQueueBundle\Entity\JobStatus;

class JobStatusRepository
{
    /** @var ManagerRegistry */
    private $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function isUserEnabled(string $command, array $arguments): bool
    {
        /** @var JobStatus $jobStatus */
        $jobStatus = $this->getEntityRepository()->findOneBy([
            'command' => $command,
            'arguments' => ($arguments) ? json_encode($arguments) : null
        ]);

        if ($jobStatus instanceof JobStatus) {
            return $jobStatus->getEnabled();
        }

        return true;
    }

    public function findBy(array $arguments): ?JobStatus
    {
        $jobStatus = $this->getEntityRepository()->findOneBy($arguments);

        if ($jobStatus instanceof JobStatus) {
            return $jobStatus;
        }

        return null;
    }

    public function store(JobStatus $jobStatus): void
    {
        $this->getEntityManager()->persist($jobStatus);
        $this->getEntityManager()->flush($jobStatus);
    }

    private function getEntityRepository(): EntityRepository
    {
        $repository = $this->getEntityManager()->getRepository(JobStatus::class);

        if ($repository instanceof EntityRepository) {
            return $repository;
        }

        throw new \RuntimeException(sprintf('Doctrine returned an invalid repository for entity JobStatus'));
    }

    private function getEntityManager(): EntityManager
    {
        $manager = $this->doctrine->getManager();

        if ($manager instanceof EntityManagerInterface && !$manager->isOpen()) {
            $manager = $this->doctrine->resetManager();
        }

        if ($manager instanceof EntityManager) {
            return $manager;
        }

        throw new \RuntimeException(sprintf('Doctrine returned an invalid type for entity manager'));
    }
}
