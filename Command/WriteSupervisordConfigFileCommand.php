<?php

namespace Markup\JobQueueBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This command adds another command to the job queue
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
            ->setDescription('Writes a supervisord config file to the control the job queue, based on the queue config')
            ->addArgument(
                'server',
                InputArgument::REQUIRED,
                'The server for which this file is being generated. Should correspond to the configuration of queues'
            )
            ->addArgument(
                'unique_environment',
                InputArgument::REQUIRED,
                'A string representing the unique environment. E.G pre-staging'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $writer = $this->getContainer()->get('markup_admin_job_queue.supervisord_config_file.writer');
        $env = $input->getArgument('unique_environment');
        $server = $input->getArgument('server');

        $output->writeln('Started writing queue configuration');
        $writer->writeConfig($env, $server);
        $output->writeln('Finished writing queue configuration');
    }
}
