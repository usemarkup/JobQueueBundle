<?php

namespace Markup\JobQueueBundle\Model;

/**
 * Represents a RabbitMq Queue
 */
class Queue
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $vhost;

    /**
     * @var string
     */
    private $state;

    /**
     * @var string
     */
    private $consumerCount;

    /**
     * @var string
     */
    private $messages;

    /**
     * @var string
     */
    private $messagesReady;

    /**
     * @var string
     */
    private $messagesUnacknowledged;
    /**
     * @var \DateTime
     */
    private $idleSince;

    public function __construct(
        $name,
        $vhost,
        $state,
        $consumerCount,
        $messages,
        $messagesReady,
        $messagesUnacknowledged,
        \DateTime $idleSince
    ) {
        $this->name = $name;
        $this->vhost = $vhost;
        $this->state = $state;
        $this->consumerCount = $consumerCount;
        $this->messages = $messages;
        $this->messagesReady = $messagesReady;
        $this->messagesUnacknowledged = $messagesUnacknowledged;
        $this->idleSince = $idleSince;
    }

    /**
     * Takes the response from the RabbitMq Api for queues and uses it to build an instance of this class
     *
     * @param  array $response
     * @return Queue
     */
    public static function constructFromApiResponse(array $response)
    {
        return new self(
            isset($response['name']) ? $response['name'] : 'undefined',
            isset($response['vhost']) ? $response['vhost'] : 'undefined',
            isset($response['state']) ? $response['state'] : 'unknown',
            isset($response['consumers']) ? $response['consumers'] : 0,
            isset($response['messages']) ? $response['messages'] : 0,
            isset($response['messages_ready']) ? $response['messages_ready'] : 0,
            isset($response['messages_unacknowledged']) ? $response['messages_unacknowledged'] : 0,
            isset($response['idle_since']) ? new \DateTime($response['idle_since']) : new \DateTime('now')
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getVhost()
    {
        return $this->vhost;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @return string
     */
    public function getConsumerCount()
    {
        return $this->consumerCount;
    }

    /**
     * @return string
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * @return string
     */
    public function getMessagesReady()
    {
        return $this->messagesReady;
    }

    /**
     * @return string
     */
    public function getMessagesUnacknowledged()
    {
        return $this->messagesUnacknowledged;
    }

    /**
     * @return \DateTime
     */
    public function getIdleSince()
    {
        return $this->idleSince;
    }
}
