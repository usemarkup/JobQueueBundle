<?php

namespace Markup\JobQueueBundle\Command;

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
        ->setName('markup:job_queue:queue:view_all')
            ->setDescription('Outputs a list of available job queues that have been configured');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configurations = $this->getContainer()->get('jobby')->getQueueConfigurations();

        $table = $this->getHelperSet()->get('table');
        $table->setHeaders(['server', 'name', 'count']);

        foreach ($configurations as $configuration) {
            $row = [];
            $row[] = $configuration->getServer();
            $row[] = $configuration->getName();
            $row[] = $configuration->getCount();
            $table->addRow(
                $row
            );
        }
        $table->render($output);
    }
}
