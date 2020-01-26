<?php

namespace Markup\JobQueueBundle\Repository;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Markup\JobQueueBundle\Entity\JobLog;
use Markup\JobQueueBundle\Form\Data\SearchJobLogs as SearchJobLogsData;
use Markup\JobQueueBundle\Model\JobLogCollection;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;

/**
 * Get and Set JobLogs (from redis)
 */
class JobLogRepository
{
    // 2 weeks
    private const DEFAULT_LOG_TTL = '1209600';

    /**
     * @var int
     */
    private $ttl;

    /**
     * @var bool
     */
    private $shouldClearLogForCompleteJob;

    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    public function __construct(
        ManagerRegistry $doctrine,
        ?int $ttl = null
    ) {
        $this->shouldClearLogForCompleteJob = false;
        $this->doctrine = $doctrine;
        $this->ttl = $ttl ?: self::DEFAULT_LOG_TTL;
    }

    public function saveFailure(string $uuid, string $output, int $exitCode): void
    {
        $log = $this->findJobLog($uuid);

        if (!$log) {
            return;
        }

        $log->setStatus(JobLog::STATUS_FAILED);

        if (!$log->getCompleted()) {
            $log->setCompleted(new \DateTime());
        }

        $log->setOutput($output);
        $log->setExitCode($exitCode);

        $this->save($log);
    }

    public function saveOutput(string $uuid, string $output): void
    {
          $log = $this->findJobLog($uuid);

          if (!$log) {
              return;
          }

          $log->setOutput($output);
          $this->save($log);
    }

    public function getJobLogs(SearchJobLogsData $data, $maxPerPage = 10, $currentPage = 1): Pagerfanta
    {
        $query = $this->getJobLogQuery($data);

        $jobLogs = new Pagerfanta(
            new DoctrineORMAdapter($query, true, false)
        );

        $jobLogs->setMaxPerPage($maxPerPage);
        $jobLogs->setCurrentPage($currentPage);

        return $jobLogs;
    }

    public function getJobLogCollection(SearchJobLogsData $data, $limit = 10): JobLogCollection
    {
        $query = $this->getJobLogQuery($data, $limit);

        $collection = new JobLogCollection();

        foreach ($query->getResult() as $row) {
            $collection->add($row);
        }

        return $collection;
    }

    private function getJobLogQuery(SearchJobLogsData $data, ?int $limit = null): Query
    {
        $qb = $this->getEntityRepository()->createQueryBuilder('j');
        if ($data->getId()) {
            $qb->where($qb->expr()->like('j.uuid', ':uuid'))
                ->setParameter(':uuid', $data->getId());
        }

        if ($data->getSince()) {
            $qb->andWhere($qb->expr()->gte('j.added', ':after'))
                ->setParameter(':after', $data->getSince());
        }

        if ($data->getBefore()) {
            $qb->andWhere($qb->expr()->lte('j.added', ':before'))
                ->setParameter(':before', $data->getBefore());
        }

        if ($data->getStatus()) {
            $qb->andWhere($qb->expr()->eq('j.status', ':status'))
                ->setParameter(':status', $data->getStatus());
        }

        if ($data->getCommand()) {
            $qb->andWhere($qb->expr()->like('j.command', ':command'))
                ->setParameter(':command', $data->getCommand().'%');
        }

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        $qb->orderBy('j.added', 'DESC');

        return $qb->getQuery();
    }

    public function setTtl(?int $ttl = null): void
    {
        $this->ttl = $ttl;
    }

    public function setShouldClearLogForCompleteJob(bool $shouldClearLogForCompleteJob): void
    {
        $this->shouldClearLogForCompleteJob = $shouldClearLogForCompleteJob;
    }

    public function findJobLog(string $uuid): ?JobLog
    {
        $jobLog = $this->getEntityRepository()->findOneBy(['uuid' => $uuid]);
        if ($jobLog instanceof JobLog) {
            return $jobLog;
        }

        return null;
    }

    /**
     * Removes all jobs older than ($this->ttl - 86400 seconds) from all secondary indexes
     */
    public function removeExpiredJobs(): void
    {
        $interval = new \DateInterval(sprintf('PT%sS', $this->ttl - 86400));
        $before = (new \DateTime('now'))->sub($interval)->format('U');

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->delete(JobLog::class, 'j')
            ->where($qb->expr()->lte('j.added', ':before'))
            ->setParameter(':before', $before);

        $qb->getQuery()->execute();
    }

    public function add(JobLog $jobLog): void
    {
        $this->getEntityManager()->persist($jobLog);
        $this->getEntityManager()->flush($jobLog);
    }

    public function save(JobLog $jobLog): void
    {
        if ($this->shouldClearLogForCompleteJob) {
            if ($jobLog->getStatus() === JobLog::STATUS_COMPLETE) {
                $this->getEntityManager()->remove($jobLog);
            }
        }

        $this->getEntityManager()->flush($jobLog);
    }

    private function getEntityRepository(): EntityRepository
    {
        $repository = $this->getEntityManager()->getRepository(JobLog::class);

        if ($repository instanceof EntityRepository) {
            return $repository;
        }

        throw new \RuntimeException(sprintf('Doctrine returned an invalid repository for entity JobLog'));
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
