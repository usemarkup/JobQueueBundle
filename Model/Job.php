<?php

namespace Markup\JobQueueBundle\Model;

use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class Job
{
    /**
     * @var string The topic of this job
     */
    public $topic = 'default';

    /**
     * @var array The job arguments
     */
    public $args = [];

    final public function __construct(
        array $args = array(),
        $topic = 'default'
    ) {
        $this->args = $args;
        $this->topic = $topic;
    }

    public function getTopic()
    {
        return $this->topic;
    }

    public function getArgs()
    {
        return $this->args;
    }

    /**
     * @param ContainerInterface|null $container
     */
    abstract public function run(ContainerInterface $container);

    /**
     * @return To be run after job constructed to check arguments are correct
     */
    public function validate()
    {
    }
}
