<?php

namespace Markup\JobQueueBundle\Job;

use Markup\JobQueueBundle\Exception\InvalidJobArgumentException;
use Markup\JobQueueBundle\Model\Job;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Process\Process;

/**
 * SleepJob uses the process component to pause working for a period of time. Used to test messge consumption rates
 */
class SleepJob extends Job
{
    /**
     * {@inheritdoc}
     */
    public function validate()
    {
        if (!isset($this->args['time'])) {
            throw new InvalidJobArgumentException('`time` must be set');
        }
        if (!is_numeric($this->args['time'])) {
            throw new InvalidJobArgumentException('time must be an integer');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function run(ParameterBagInterface $parameterBag): string
    {
        $process = new Process(sprintf('sleep %s', $this->args['time']));
        $process->run();

        return '';
    }
}
