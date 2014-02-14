<?php

namespace Phoenix\Bundle\JobQueueBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ViewConfiguredQueuesCommand extends ContainerAwareCommand
{

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('phoenix:job_queue:queue:view_all')
            ->setDescription('Outputs a list of available job queues that have been configured');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $queues = $this->getContainer()->get('jobby')->getQueues();

        $output->writeln(implode(',', $queues));
    }
}
