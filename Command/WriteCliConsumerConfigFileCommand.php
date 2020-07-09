<?php

namespace Markup\JobQueueBundle\Command;

use Markup\JobQueueBundle\Service\CliConsumerConfigFileWriter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This command writes a series of rabbitmq-cli-consumer config files (one per consumer)
 */
class WriteCliConsumerConfigFileCommand extends Command
{
    protected static $defaultName = 'markup:job_queue:cli_consumer_config:write';

    /**
     * @var CliConsumerConfigFileWriter
     */
    private $cliConsumerConfigFileWriter;

    public function __construct(CliConsumerConfigFileWriter $cliConsumerConfigFileWriter)
    {
        $this->cliConsumerConfigFileWriter = $cliConsumerConfigFileWriter;

        parent::__construct();
    }

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setDescription('Writes a series of rabbitmq-cli-consumer config files (one per consumer)')
            ->addArgument(
                'unique_environment',
                InputArgument::REQUIRED,
                'A string representing the unique environment. E.G pre-staging'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $env = $input->getArgument('unique_environment');
        $output->writeln('Started writing consumer configurations');
        $this->cliConsumerConfigFileWriter->writeConfig($env);
        $output->writeln('Finished writing consumer configurations');
    }
}
