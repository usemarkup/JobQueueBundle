<?php

namespace Markup\JobQueueBundle\Repository;

use Predis\Client as Predis;

/**
 * Repository that saves and fetches a health check to redis when the
 * make sure the recurring cron job is running
 */
class CronHealthRepository
{
    const REDIS_KEY = 'markup_job_queue:recurring_cron_health';

    /**
     * @var Predis
     */
    private $predis;

    /**
     * JobLogRepository constructor.
     * @param Predis $predis
     */
    public function __construct(Predis $predis)
    {
        $this->predis = $predis;
    }

    /**
     * Set this on every cron run to make sure job is marked as healty
     */
    public function set()
    {
        $this->predis->setex(self::REDIS_KEY, 65, 'true');
    }

    /**
     * @return string|null
     */
    public function get()
    {
        return $this->predis->get(self::REDIS_KEY);
    }
}

