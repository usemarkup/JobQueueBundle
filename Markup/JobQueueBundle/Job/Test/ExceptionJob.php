<?php

namespace Phoenix\Bundle\JobQueueBundle\Job\Test;

use BCC\ResqueBundle\Job;
use Phoenix\Bundle\JobQueueBundle\Exception\JobFailedException;

class ExceptionJob extends Job
{
    public function __construct()
    {
        $this->queue = 'test';
    }

    public function run($args)
    {
        throw new JobFailedException('Test tob throws an exception');
    }
}
