<?php

namespace Markup\JobQueueBundle\Model;

class QueueConfiguration
{
    /**
     * @var string
     */
    private $server;

    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $count;

    public function __construct($server, array $config)
    {
        $this->server = $server;
        $this->name = $config['name'];
        $this->count = $config['count'];
    }

    public function getName()
    {
        return $this->name;
    }

    public function getCount()
    {
        return $this->count;
    }

    public function getServer()
    {
        return $this->server;
    }
}
