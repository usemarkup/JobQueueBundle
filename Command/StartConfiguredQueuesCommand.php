<?php

namespace Markup\JobQueueBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StartConfiguredQueuesCommand extends ContainerAwareCommand
{

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('markup:job_queue:queue:start_all')
            ->setDescription('Starts a list of available job queues that have been configured, with one worker per queue, DO NOT USE IN PRODUCTION');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->getContainer()->get('kernel')->getEnvironment() !== 'dev') {
            throw new \Exception('Only for use in development');
        }

        $queues = $this->getContainer()->get('jobby')->getQueues();

        $i = 0;
        foreach ($queues as $server => $qs) {
            foreach ($qs as $queue) {
                $queueServer = sprintf('%s-%s', $queue, $server);

                $command = $this->getApplication()->find('bcc:resque:worker-start');

                $arguments = array(
                    'command' => 'bcc:resque:worker-start',
                    'queues'    => $queueServer
                );

                $input = new ArrayInput($arguments);
                $command->run($input, $output);
                $i++;
            }
        }

        $output->writeln(sprintf('<info>Started %s queues</info>', $i));
    }
}
