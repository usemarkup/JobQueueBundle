<?php

namespace Markup\JobQueueBundle\Job;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Markup\JobQueueBundle\Model\Job;

class MonologErrorJob extends Job
{
    public function run(ContainerInterface $container)
    {
        $logger = $container->get('logger');
        $logger->error('Test job produced an error');
    }
}
