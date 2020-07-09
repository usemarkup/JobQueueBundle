<?php

namespace Markup\JobQueueBundle\Job;

use Markup\JobQueueBundle\Exception\JobFailedException;
use Markup\JobQueueBundle\Exception\InvalidJobArgumentException;
use Markup\JobQueueBundle\Model\Job;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
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
    public function run(ParameterBagInterface $parameterBag): string
    {
        ini_set('max_execution_time', 7200);
        $command = [];

        $command[] = $this->getConsolePath($parameterBag->get('markup_job_queue.console_dir'));

        /**
         * This is less than ideal, but trying to support legacy and v3 and v4 symfony
         */
        // If the command has been provided like `do:something "hello"` allow it through
        if (stripos($this->args['command'], '"') !== false) {
            $command[] = $this->args['command'];
        } else {
            // If the command has been provided like `do:something hello` split so the escaping is correct
            $command = array_merge($command, explode(' ', $this->args['command']));
        }


        $uuid = isset($this->args['uuid']) ? $this->args['uuid']: null;
        if($uuid) {
            $command[] = sprintf('--uuid=%s', $uuid);
        }
        if ($parameterBag->get('kernel.debug') !== true) {
            $command[] = sprintf('--no-debug');
        }

        $command[] = sprintf('--env=%s', $parameterBag->get('kernel.environment'));

        $process = new Process($command);

        if (!isset($this->args['timeout'])) {
            $this->args['timeout'] = 60;
        }
        $process->setTimeout((int) $this->args['timeout']);
        if (!isset($this->args['idleTimeout'])) {
            $this->args['idleTimeout'] = $process->getIdleTimeout();
        }
        $process->setIdleTimeout((int) $this->args['idleTimeout']);

        try {
            $process->run();

            if (!$process->isSuccessful()) {
                $message = sprintf(
                    'A job `%s` failed with topic `%s` with output:%s and the error output: %s',
                    $this->args['command'],
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
