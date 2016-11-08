<?php

namespace Markup\JobQueueBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * A collection of job logs, includes methods for providing a 'health' for the given collection
 * based on how many have successfully completed
 */
class JobLogCollection extends ArrayCollection
{
    /**
     * Returns a value between 0 and 1 representing the ratio of completed jobs in this collection
     * that completed successfully (vs failing)
     * @return float
     */
    public function getHealthRatio()
    {
        $total = $this->count();
        if ($total === 0) {
            return 0;
        }
        $completed = 0;

        foreach($this as $log) {
            if ($log->getStatus() !== JobLog::STATUS_FAILED){
                $completed++;
            }
        }

        if($completed === 0) {
            return 0;
        }
        return round($completed/$total, 2);
    }

    /**
     * Returns the average duration of completion for the jobs in this collection
     *
     * @return int
     */
    public function getAverageDuration()
    {
        $withDuration = 0;
        $totalDuration = 0;
        foreach($this as $log) {
            if ($log->getDuration()){
                $withDuration++;
                $totalDuration = $totalDuration + $log->getDuration();
            }
        }
        if (!$withDuration || !$totalDuration) {
            return 0;
        }
        return (int)floor($totalDuration/$withDuration);
    }

    /**
     * Gets the highest memory use for jobs in this collection
     *
     * @return int
     */
    public function getPeakMemoryUse()
    {
        $peak = 0;
        foreach($this as $log) {
            if ($log->getPeakMemoryUse() && $log->getPeakMemoryUse() > $peak){
                $peak = $log->getPeakMemoryUse();
            }
        }
        return $peak;
    }
}
