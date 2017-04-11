<?php

namespace Markup\JobQueueBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Markup\JobQueueBundle\Entity\ScheduledJob;
use Markup\JobQueueBundle\Model\ScheduledJobRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class ScheduledJobRepository extends EntityRepository implements ScheduledJobRepositoryInterface
{
    use ContainerAwareTrait;

    /**
     * @return array|null
     */
    public function fetchUnqueuedJobs()
    {
        $qb = $this->createQueryBuilder('job');
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

    /**
     * @param ScheduledJob $scheduledJob
     * @param bool $flush
     */
    public function save(ScheduledJob $scheduledJob, $flush = false)
    {
        $this->_em->persist($scheduledJob);

        if ($flush) {
            $this->_em->flush();
        }
    }

}
