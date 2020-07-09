<?php

namespace Markup\JobQueueBundle\Model;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

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

    /**
     * @param array  $args
     * @param string $topic
     */
    final public function __construct(
        array $args = array(),
        $topic = 'default'
    ) {
        $this->args = $args;
        $this->topic = str_replace('-', '_', $topic);
    }

    /**
     * @return string
     */
    public function getTopic()
    {
        return $this->topic;
    }

    /**
     * @return array
     */
    public function getArgs()
    {
        return $this->args;
    }

    abstract public function run(ParameterBagInterface $parameterBag): string;

    /**
     * To be run after job constructed to check arguments are correct
     */
    public function validate()
    {
    }
}
