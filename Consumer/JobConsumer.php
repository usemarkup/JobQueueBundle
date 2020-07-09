<?php

namespace Markup\JobQueueBundle\Consumer;

use Markup\JobQueueBundle\Exception\JobFailedException;
use Markup\JobQueueBundle\Exception\JobMissingClassException;
use Markup\JobQueueBundle\Model\Job;
use Markup\JobQueueBundle\Job\ConsoleCommandJob;
use Markup\JobQueueBundle\Repository\JobLogRepository;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class JobConsumer implements ConsumerInterface
{
    /**
     * @var JobLogRepository
     */
    private $jobLogRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;

    public function __construct(JobLogRepository $jobLogRepository, ParameterBagInterface $parameterBag, ?LoggerInterface $logger = null)
    {
        $this->jobLogRepository = $jobLogRepository;
        $this->parameterBag = $parameterBag;
        $this->logger = $logger ?: new NullLogger();
    }

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
            $output = $job->run($this->parameterBag);

            if (isset($data['uuid'])) {
                try {
                    $this->jobLogRepository->saveOutput(
                        $data['uuid'],
                        strval($output)
                    );
                } catch (\Throwable $t) {
                    // do nothing
                }
            }

        } catch (\Throwable $e) {
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
                try {
                    $this->jobLogRepository->saveFailure(
                        $data['uuid'],
                        strval($output),
                        $exitCode ?? 1
                    );
                } catch (\Throwable $t) {
                    // do nothing
                }
            }

            $this->logger->error(
                sprintf('Job Failed: %s', $command),
                [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'file' => $e->getFile(),
                    'trace' => $e->getTraceAsString(),
                ]
            );

            return ConsumerInterface::MSG_REJECT;
        }
    }
}
