<?php

namespace Markup\JobQueueBundle\Job;

use Markup\JobQueueBundle\Model\Job;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BadJob extends Job
{
    public function run(ContainerInterface $container)
    {
        callToUndefinedFunction();
    }
}
