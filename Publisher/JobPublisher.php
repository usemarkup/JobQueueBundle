<?php

namespace Markup\JobQueueBundle\Publisher;

use Markup\JobQueueBundle\Exception\MissingTopicException;
use Markup\JobQueueBundle\Exception\UndefinedProducerException;
use Markup\JobQueueBundle\Job\ConsoleCommandJob;
use Markup\JobQueueBundle\Model\Job;
use Markup\JobQueueBundle\Repository\JobLogRepository;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Delegates production of jobs to oldsound component
 */
class JobPublisher implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var JobLogRepository
     */
    private $jobLogRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param JobLogRepository $jobLogRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        JobLogRepository $jobLogRepository,
        LoggerInterface $logger
    ) {
        $this->jobLogRepository = $jobLogRepository;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Sends a job to RabbitMQ via oldsound producer service
     *
     * @param Job $job
     *
     * @throws MissingTopicException
     * @throws UndefinedProducerException
     */
    public function publish(Job $job)
    {
        $job->validate();

        $topic = str_replace('-', '_', $job->getTopic());
        if (!$topic) {
            throw new MissingTopicException('A job must have a topic to allow it to be published');
        }

        // ensure rabbit mq producer exists by convention of topic - throw exception if not
        $fqProducerName = sprintf('old_sound_rabbit_mq.%s_producer', $topic);
        if (!$this->container->has($fqProducerName)) {
            throw new UndefinedProducerException(sprintf("Producer for topic '%s' has not been configured", $topic));
        }

        // add the 'class' of the job as an argument to allow it to be constructed again by consumer
        $message = array_merge($job->getArgs(), ['job_class' => get_class($job)]);
        try {
            $producer = $this->container->get($fqProducerName);
            $producer->setContentType('application/json');

            // log the job as existing
            if ($job instanceof ConsoleCommandJob) {
                $log = $this->jobLogRepository->createAndSaveJobLog($job->getCommand(), $uuid = null, $job->getTopic());
                // adds the uuid to the published job
                // which allows the consumer to specify the Uuid when running the command
                $message['uuid'] = $log->getUuid();
            }

            $producer->publish(json_encode($message));
        } catch (AMQPRuntimeException $e) {
            $this->logger->error('Unable to add job to the job queue - AMQPRuntimeException - Is RabbitMQ running?:'.$e->getMessage());
        } catch (\Exception $e) {
            $this->logger->error('Unable to add job to the job queue - General Exception:'.$e->getMessage());
        }
    }
}
