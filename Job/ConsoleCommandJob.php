<?php

namespace Markup\JobQueueBundle\Job;

use Markup\JobQueueBundle\Exception\JobFailedException;
use Markup\JobQueueBundle\Exception\InvalidJobArgumentException;
use Markup\JobQueueBundle\Model\Job;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

/**
 * This job runs a console command. Note this runs inside a 'Process' isolating errors that happen during the command.
 * this means all exceptions are caught and the message can be rejected using ConsumerInterface::MSG_REJECT
 */
class ConsoleCommandJob extends Job
{
    /**
     * {inheritdoc}
     */
    public function run(ContainerInterface $container): string
    {
        ini_set('max_execution_time', 7200);
        $command = $this->args['command'];
        $uuid = isset($this->args['uuid']) ? $this->args['uuid']: null;
        if($uuid) {
            $command = sprintf('%s --uuid=%s', $command, $uuid);
        }
        if ($container->get('kernel')->isDebug() !== true) {
            $command = sprintf('%s --no-debug', $command);
        }

        // get the absolute path of the console and the environment
        $command = sprintf(
            '%s %s --env=%s',
            $this->getConsolePath($container->getParameter('markup_job_queue.console_dir')),
            $command,
            $container->get('kernel')->getEnvironment()
        );

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
                $message = sprintf(
                    'A job `%s` failed with topic `%s` with output:%s and the error output: %s',
                    $command,
                    $this->topic,
                    $process->getOutput(),
                    $process->getErrorOutput()
                );
                throw new JobFailedException($message, $process->getExitCode());
            }
            return strval($process->getOutput());
         } catch (ProcessTimedOutException $e) {
            if ($e->isGeneralTimeout()) {
                throw new JobFailedException(sprintf('Timeout: %s', $e->getMessage()), $process->getExitCode(), 0, $e);
            }

            if ($e->isIdleTimeout()) {
                throw new JobFailedException(sprintf('Idle Timeout: %s', $e->getMessage()), $process->getExitCode(), 0, $e);
            }

            throw $e;
        } catch (JobFailedException $e) {
            throw $e;
        }  catch (RuntimeException $e) {
            // if process has been signalled then use the termSignal as the exit code
            try {
                $code = sprintf('SIGNAL %s', $process->getTermSignal());
            } catch(\Exception $e) {
                // RuntimeException may have been thrown for some other reason
                $code = 'UNKNOWN';
            }
            throw new JobFailedException($e->getMessage(), $code);
        } catch (\Exception $e) {
            throw new JobFailedException($e->getMessage());
        }
    }

    /**
     * {inheritdoc}
     */
    public function validate()
    {
        if (!isset($this->args['command'])) {
            throw new InvalidJobArgumentException('`command` must be set');
        }
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return $this->getArgs()['command'];
    }

    /**
     * @param  string $kernelDir
     * @return string
     */
    private function getConsolePath($kernelDir)
    {
        $finder = new Finder();
        $finder->name('console')->depth(0)->in($kernelDir);
        $results = iterator_to_array($finder);
        $file = current($results);

        return sprintf('%s/%s', $file->getPath(), $file->getBasename());
    }
}
