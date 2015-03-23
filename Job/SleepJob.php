<?php

namespace Markup\JobQueueBundle\Job;

use Markup\JobQueueBundle\Model\Job;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Process\Process;

class SleepJob extends Job
{
    public function validate()
    {
        if (!isset($this->args['time'])) {
            throw new InvalidJobArgumentException('`time` must be set');
        }
    }

    public function run(ContainerInterface $container)
    {
        $process = new Process(sprintf('sleep %s', $this->args['time']));
        $process->run();
    }
}
