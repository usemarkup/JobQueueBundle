<?php

namespace Markup\JobQueueBundle\Consumer;

use Markup\JobQueueBundle\Exception\JobFailedException;
use Markup\JobQueueBundle\Exception\JobMissingClassException;
use Markup\JobQueueBundle\Model\Job;
use Markup\JobQueueBundle\Job\ConsoleCommandJob;
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
        try {
            $data = json_decode($message->body, $array = true);
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
                $this->container->get('markup_job_queue.repository.job_log')->saveOutput(
                    $data['uuid'],
                    $output
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
                $this->container->get('markup_job_queue.repository.job_log')->saveFailure(
                    $data['uuid'],
                    $output,
                    $exitCode
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

}
