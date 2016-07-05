<?php

namespace Markup\JobQueueBundle\Reader;

use Doctrine\Common\Collections\ArrayCollection;
use Markup\JobQueueBundle\Model\Queue;
use Markup\RabbitMq\ApiFactory;

/**
 * Reads Information about job queues from RabbitMq
 */
class QueueReader
{
    /**
     * @var ApiFactory
     */
    private $api;

    /**
     * @var string
     */
    private $vhost;

    /**
     * @param ApiFactory $apiFactory
     * @param            $vhost
     */
    public function __construct(
        ApiFactory $apiFactory,
        $vhost
    ) {
        $this->api = $apiFactory;
        $this->vhost = $vhost;
    }

    /**
     * Get an a collection of Queue objects representing information
     * about all queues
     *
     * @return ArrayCollection
     */
    public function getQueues()
    {
        $collection = new ArrayCollection();
        try{
            $apiResponse = $this->api->queues()->all($this->vhost);
            foreach($apiResponse as $q) {
                $collection->add(Queue::constructFromApiResponse($q));
            }
        } catch(\Exception $e) {
            throw $e;
            return $collection;
        }
        return $collection;
    }

    /**
     * Can a connection to rabbitMq be established at all?
     *
     * @return boolean
     */
    public function isAlive()
    {
        try{
            $this->api->alivenessTest('test');
            return true;
        } catch(\Exception $e) {
            return false;
        }
    }

}
