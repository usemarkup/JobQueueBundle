<?php

namespace Markup\JobQueueBundle\Publisher;

use Markup\JobQueueBundle\Exception\MissingTopicException;
use Markup\JobQueueBundle\Model\Job;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use Symfony\Component\DependencyInjection\ContainerAware;

/**
 * Delegates production of jobs to oldsound component
 */
class JobPublisher extends ContainerAware
{

    const DEFAULT_TOPIC = 'system-slow';

    public function publish(Job $job)
    {
        $logger = $this->container->get('logger');
        $job->validate();
        $topic = str_replace('-', '_', $job->getTopic());
        if (!$topic) {
            throw new MissingTopicException('A job must have a topic to allow it to be published');
        }

        // ensure rabbit mq producer exists by convention of topic - throw exception if not
        $fqProducerName = sprintf('old_sound_rabbit_mq.%s_producer', $topic);
        if (!$this->container->has($fqProducerName)) {
            $logger->error(sprintf('A job has attempted to add to the topic `%s` which doesnt exist. Please reference a valid topic. Defaulting to `%s`', $topic, self::DEFAULT_TOPIC));
            $fqProducerName = sprintf('old_sound_rabbit_mq.%s_producer', self::DEFAULT_TOPIC);
        }
        // add the 'class' of the job as an argument to allow it to be constructed again by consumer
        $message = array_merge($job->getArgs(), ['job_class' => get_class($job)]);
        try {
        } catch (AMQPRuntimeException $e) {
            $producer = $this->container->get($fqProducerName);
            $producer->setContentType('application/json');
            $producer->publish(json_encode($message));
            $logger->error('Unable to add job to the job queue - AMQPRuntimeException - Is RabbitMQ running?:'.$e->getMessage());
        } catch (\Exception $e) {
            $logger->error('Unable to add job to the job queue - General Exception:'.$e->getMessage());
        }
    }
}
