<?php

namespace Markup\JobQueueBundle\Job;

use Markup\JobQueueBundle\Exception\JobFailedException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Markup\JobQueueBundle\Model\Job;

class ExceptionJob extends Job
{
    public function run(ContainerInterface $container)
    {
        throw new JobFailedException('Test job throws an exception');
    }
}
