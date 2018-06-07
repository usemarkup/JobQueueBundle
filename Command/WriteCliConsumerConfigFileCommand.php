<?php

namespace Markup\JobQueueBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This command writes a series of rabbitmq-cli-consumer config files (one per consumer)
 */
class WriteCliConsumerConfigFileCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('markup:job_queue:cli_consumer_config:write')
            ->setDescription('Writes a series of rabbitmq-cli-consumer config files (one per consumer)')
            ->addArgument(
                'unique_environment',
                InputArgument::REQUIRED,
                'A string representing the unique environment. E.G pre-staging'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $writer = $this->getContainer()->get('markup_job_queue.writer.cli_consumer_config_file');
        $env = $input->getArgument('unique_environment');
        $output->writeln('Started writing consumer configurations');
        $writer->writeConfig($env);
        $output->writeln('Finished writing consumer configurations');
    }
}
