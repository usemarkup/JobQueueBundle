<?php

namespace Markup\JobQueueBundle\Job;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Markup\JobQueueBundle\Model\Job;

/**
 * Logs an error and returns as is sucessful. Allows you to test Jobs that handle all errors correctly
 */
class MonologErrorJob extends Job
{
    /**
     * {@inheritdoc}
     */
    public function run(ContainerInterface $container)
    {
        $logger = $container->get('logger');
        $logger->error('Test job produced an error');
    }
}
