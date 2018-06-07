<?php

namespace Markup\JobQueueBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This command writes a supervisord config file to monitor rabbitmq consumers
 */
class WriteSupervisordConfigFileCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('markup:job_queue:supervisord_config:write')
            ->setDescription('Writes a supervisord config file to monitor rabbitmq consumers')
            ->addArgument(
                'unique_environment',
                InputArgument::REQUIRED,
                'A string representing the unique environment. E.G pre-staging'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $writer = $this->getContainer()->get('markup_job_queue.writer.supervisord_config_file');
        $env = $input->getArgument('unique_environment');
        $output->writeln('Started writing queue configuration');
        $writer->writeConfig($env);
        $output->writeln('Finished writing queue configuration');
    }
}
