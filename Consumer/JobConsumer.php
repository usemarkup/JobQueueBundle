<?php

namespace Markup\JobQueueBundle\Consumer;

use Markup\JobQueueBundle\Exception\JobFailedException;
use Markup\JobQueueBundle\Exception\JobMissingClassException;
use Markup\JobQueueBundle\Model\Job;
use Markup\JobQueueBundle\Job\ConsoleCommandJob;
use Markup\JobQueueBundle\Repository\JobLogRepository;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class JobConsumer implements ConsumerInterface, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function execute(AMQPMessage $message)
    {
        $data = json_decode($message->body, true);

        try {
            if (!isset($data['job_class'])) {
                throw new JobMissingClassException('`job_class` must be set in the message');
            }
            // rehydrate job class
            $jobClass = $data['job_class'];
            unset($data['job_class']);
            $job = new $jobClass($data);
            if (!$job instanceof Job) {
                throw new \LogicException('This consumer can only consume instances of Markup\JobQueueBundle\Model\Job but job of following type was given: '.get_class($job));
            }
            $job->validate();
            $output = $job->run($this->container);

            if (isset($data['uuid'])) {
                $this->getJobLogRepository()->saveOutput(
                    $data['uuid'],
                    strval($output)
                );
            }

        } catch (\Exception $e) {
            $command = '';

            if ((isset($job))) {
                $command = get_class($job);
                if ($job instanceof ConsoleCommandJob) {
                    $command = $job->getCommand();
                }
            }

            $exitCode = null;
            $output = sprintf('%s - %s', $e->getMessage(), $e->getTraceAsString());
            if ($e instanceof JobFailedException) {
                $exitCode = intval($e->getExitCode());
            }
            // save failure if job had uuid
            if (isset($data['uuid'])) {
                $this->getJobLogRepository()->saveFailure(
                    $data['uuid'],
                    strval($output),
                    $exitCode ?? 1
                );
            }

            $this->container->get('logger')->error(sprintf('Job Failed: %s', $command), [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ConsumerInterface::MSG_REJECT;
        }
    }

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    private function getJobLogRepository(): JobLogRepository
    {
        $jobLogRepository = $this->container->get('markup_job_queue.repository.job_log');

        if (!$jobLogRepository instanceof JobLogRepository) {
            throw new \LogicException('Could not find the JobLogRepository in the container');
        }

        return $jobLogRepository;
    }

}
