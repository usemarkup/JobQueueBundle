<?php

namespace Markup\JobQueueBundle\Job;

use Markup\JobQueueBundle\Exception\JobFailedException;
use Markup\JobQueueBundle\Model\Job;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ExceptionJob extends Job
{
    /**
     * {@inheritdoc}
     */
    public function run(ParameterBagInterface $parameterBag): string
    {
        throw new JobFailedException('Test job throws an exception');
    }
}
