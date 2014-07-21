<?php

namespace Markup\JobQueueBundle\Job;

use BCC\ResqueBundle\ContainerAwareJob;
use Markup\JobQueueBundle\Exception\JobFailedException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

/**
 * This job adds a console command to the queue
 */
class ConsoleCommandJob extends ContainerAwareJob
{
    /**
     * The time before the process component times out
     * @var integer
     */
    private $timeout;

    /**
     * The time before the process component times out (if idle)
     * @var integer
     */
    private $idleTimeout;

    public function run($args)
    {
        $env = $args['kernel.environment'];
        $debug = $args['kernel.debug'];
        $command = $args['command'];

        // get the absolute path of the console and the environment
        $command = sprintf('%s %s --env=%s', $this->getConsolePath(), $command, $env);

        if ($debug !== true) {
            $command = sprintf('%s --no-debug', $command);
        }

        $process = new Process($command);

        $timeout = 60;
        $idleTimeout = 60;

        if (isset($args['timeout'])) {
            $timeout = $args['timeout'];
        }

        if (isset($args['idleTimeout'])) {
            $idleTimeout = $args['idleTimeout'];
        }

        try {
            $process->setTimeout((int) $timeout);
            $process->setIdleTimeout((int) $idleTimeout);
            $process->run();

            $logger = $this->getContainer()->get('logger');

            if (!$process->isSuccessful()) {
                $message = sprintf('A job failed on the queue `%s` with output:%s and the error output: %s', $this->queue, $process->getOutput(), $process->getErrorOutput());
                $logger->error($message);
                $logger->error('Timeout values for this job are `%s` and `%s` seconds', , );
                throw new JobFailedException($message);
            }

        } catch (\Exception $e) {
            throw new JobFailedException($e->getMessage());
        }
    }

    public function setCommand($command)
    {
        $this->args['command'] = $command;
    }

    public function setQueue($queue)
    {
        $this->queue = $queue;
    }

    public function setTimeout($time)
    {
        $this->args['timeout'] = $time;
    }

    public function setIdleTimeout($time)
    {
        $this->args['idleTimeout']  = $time;
    }

    private function getConsolePath()
    {
        $finder = new Finder();
        $finder->name('console')->depth(0)->in($this->args['kernel.root_dir']);
        $results = iterator_to_array($finder);
        $file = current($results);

        return sprintf('%s/%s', $file->getPath(), $file->getBasename());
    }
}
