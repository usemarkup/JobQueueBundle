<?php

namespace Markup\JobQueueBundle\Job\Test;

use BCC\ResqueBundle\Job;
use Symfony\Component\Process\Process;

class SleepJob extends Job
{
    public function __construct($time = 30)
    {
        $this->queue = 'test';
        $this->args = ['time' => $time];
    }

    public function run($args)
    {
        $process = new Process(sprintf('sleep %s', $args['time']));
        $process->run();
    }
}
