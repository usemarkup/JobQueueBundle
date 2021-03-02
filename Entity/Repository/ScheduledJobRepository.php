<?php

namespace Markup\JobQueueBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Markup\JobQueueBundle\Entity\ScheduledJob;
use Markup\JobQueueBundle\Model\ScheduledJobRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class ScheduledJobRepository implements ScheduledJobRepositoryInterface
{
    use DoctrineOrmAwareRepositoryTrait;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
        $this->entity = ScheduledJob::class;
    }

    /**
     * @return ?iterable<ScheduledJob>
     */
    public function fetchUnqueuedJobs()
    {
        $qb = $this->getEntityRepository()
            ->createQueryBuilder('job');
        $qb->andWhere($qb->expr()->eq('job.queued', ':queued'));
        $qb->andWhere($qb->expr()->lt('job.scheduledTime', ':now'));
        $qb->setParameter(':queued', false);
        $qb->setParameter(':now', new \DateTime('now'));
        $jobs = $qb->getQuery()->getResult();

        if (count($jobs) > 0) {
            return $jobs;
        }

        return null;
    }

    public function isJobScheduledWithinRange(
        string $job,
        \DateTime $rangeFrom,
        \DateTime $rangeTo,
        ?array $arguments
    ): bool {
        $qb = $this->getEntityRepository()
            ->createQueryBuilder('j')
            ->select('COUNT(1)')
            ->where('j.job = :job')
            ->andWhere('j.scheduledTime >= :from')
            ->andWhere('j.scheduledTime <= :to')
            ->setParameter('job', $job)
            ->setParameter('from', $rangeFrom)
            ->setParameter('to', $rangeTo);
            
        if ($arguments) {
            $qb
                ->andWhere('j.arguments = :arguments')
                ->setParameter('arguments', serialize($arguments));
        }
        try {
            return boolval($qb->getQuery()->getSingleScalarResult());
        } catch (NoResultException|NonUniqueResultException $e) {
            return false;
        }
    }

    public function hasUnQueuedDuplicate(string $job, ?array $arguments): bool
    {
        $qb = $this->getEntityRepository()
            ->createQueryBuilder('j')
            ->select('COUNT(1)')
            ->where('j.job = :job')
            ->andWhere('j.queued = :queued')
            ->andWhere('j.scheduledTime <= :now')
            ->setParameter('queued', false)
            ->setParameter('job', $job)
            ->setParameter('now', (new \DateTime()));
        
        if ($arguments) {
            $qb
                ->andWhere('j.arguments = :arguments')
                ->setParameter('arguments', serialize($arguments));
        }
        
        try {
            return boolval($qb->getQuery()->getSingleScalarResult());
        } catch (NoResultException|NonUniqueResultException $e) {
            return false;
        }
    }
    
    public function save(ScheduledJob $scheduledJob, $flush = false): void
    {
        $this->persist($scheduledJob);
        
        if ($flush) {
            $this->flush($scheduledJob);
        }
    }
}
