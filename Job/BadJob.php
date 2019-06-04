<?php

namespace Markup\JobQueueBundle\Job;

use Markup\JobQueueBundle\Model\Job;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The expected behaviour of BadJob is to call an undefined function, causing a fatal error
 * Rabbitmq will need to be appropriately configured to handle this in the way you want
 */
class BadJob extends Job
{
    /**
     * {@inheritdoc}
     */
    public function run(ContainerInterface $container)
    {
        throw new \Exception('Error');
    }
}
