<?php

namespace Markup\JobQueueBundle\Job;

use Markup\JobQueueBundle\Exception\JobFailedException;
use Markup\JobQueueBundle\Model\Job;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

/**
 * This job runs a console command
 */
class ConsoleCommandJob extends Job
{
    public function run(ContainerInterface $container)
    {
        ini_set('max_execution_time', 7200);
        $command = $this->args['command'];

        // get the absolute path of the console and the environment
        $command = sprintf('%s %s --env=%s', $this->getConsolePath($container->get('kernel')->getRootdir()), $command, $container->get('kernel')->getEnvironment());
        if ($container->get('kernel')->isDebug() !== true) {
            $command = sprintf('%s --no-debug', $command);
        }

        $process = new Process($command);
        if (!isset($this->args['timeout'])) {
            $this->args['timeout'] = 60;
        }
        $process->setTimeout((int) $this->args['timeout']);
        if (!isset($this->args['idleTimeout'])) {
            $this->idleTimeout = $this->args['idleTimeout'];
        }
        $process->setIdleTimeout((int) $this->args['idleTimeout']);

        try {
            $process->run();

            if (!$process->isSuccessful()) {
                $message = sprintf('A job `%s` failed with topic `%s` with output:%s and the error output: %s', $command, $this->topic, $process->getOutput(), $process->getErrorOutput());
                throw new JobFailedException($message);
            }
        } catch (\Exception $e) {
            throw new JobFailedException($e->getMessage());
        }
    }

    public function validate()
    {
        if (!isset($this->args['command'])) {
            throw new InvalidJobArgumentException('`command` must be set');
        }
    }

    private function getConsolePath($kernelDir)
    {
        $finder = new Finder();
        $finder->name('console')->depth(0)->in($kernelDir);
        $results = iterator_to_array($finder);
        $file = current($results);

        return sprintf('%s/%s', $file->getPath(), $file->getBasename());
    }
}
