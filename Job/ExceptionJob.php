<?php

namespace Markup\JobQueueBundle\Job;

use Markup\JobQueueBundle\Exception\JobFailedException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Markup\JobQueueBundle\Model\Job;

class ExceptionJob extends Job
{
    /**
     * {@inheritdoc}
     */
    public function run(ContainerInterface $container): string
    {
        throw new JobFailedException('Test job throws an exception');
    }
}
