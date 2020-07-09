<?php

namespace Markup\JobQueueBundle\Command;

use Markup\JobQueueBundle\Service\SupervisordConfigFileWriter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This command writes a supervisord config file to monitor rabbitmq consumers
 */
class WriteSupervisordConfigFileCommand extends Command
{
    protected static $defaultName = 'markup:job_queue:supervisord_config:write';

    /**
     * @var SupervisordConfigFileWriter
     */
    private $configFileWriter;

    public function __construct(SupervisordConfigFileWriter $configFileWriter)
    {
        parent::__construct();
        $this->configFileWriter = $configFileWriter;
    }

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setDescription('Writes a supervisord config file to monitor rabbitmq consumers')
            ->addArgument(
                'unique_environment',
                InputArgument::REQUIRED,
                'A string representing the unique environment. E.G pre-staging'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $env = $input->getArgument('unique_environment');
        $output->writeln('Started writing queue configuration');
        $this->configFileWriter->writeConfig($env);
        $output->writeln('Finished writing queue configuration');
    }
}
