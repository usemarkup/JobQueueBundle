<?php

namespace Markup\Bundle\JobQueueBundle\Job\Test;

use BCC\ResqueBundle\Job;

class BadJob extends Job
{
    public function __construct()
    {
        $this->queue = 'test';
    }

    public function run($args)
    {
        callToUndefinedFunction();
    }
}
