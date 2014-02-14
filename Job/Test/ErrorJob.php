<?php

namespace Phoenix\Bundle\JobQueueBundle\Job\Test;

use BCC\ResqueBundle\ContainerAwareJob;

class ErrorJob extends ContainerAwareJob
{
    public function __construct()
    {
        $this->queue = 'test';
    }

    public function run($args)
    {
        $logger = $this->getContainer()->get('monolog.logger.resque');
        $logger->error('Test job produced an error');
    }
}
