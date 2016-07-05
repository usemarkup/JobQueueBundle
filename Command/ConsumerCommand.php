<?php

namespace Markup\JobQueueBundle\Command;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Consumes messages from the Go consumer
 */
class ConsumerCommand extends ContainerAwareCommand
{
    const STRICT_CODE_ACK = 0;
    const STRICT_CODE_REJECT = 3;
    const STRICT_CODE_REJECT_REQUEUE = 4;
    const STRICT_CODE_NEG_ACK = 5;
    const STRICT_CODE_NEG_ACK_REQUEUE = 6;

    protected function configure()
    {
        $this
            ->addArgument('event', InputArgument::REQUIRED)
            ->setName('markup:job_queue:consumer')
            ->addOption(
                'strict-exit-code',
                null,
                InputOption::VALUE_NONE,
                'If strict_exit_code is chosen then this command will return the following exit codes. 0=ACK, 3=REJECT, 4=REJECT & REQUEUE, 5=NEG ACK, 6=NEG ACK & REQUEUE'
            );
    }

    /**
     * {inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $jsonData = base64_decode($input->getArgument('event'));
        $data = json_decode($jsonData, true);

        if (isset($data['body'])) {
            $message = new AMQPMessage($data['body'], $data['properties']);
        } else {
            $message = new AMQPMessage($jsonData);
        }

        $strict = $input->getOption('strict-exit-code');
        $consumerReturn = $this->getContainer()->get('markup_job_queue.consumer')->execute($message);

        // if not running in strict mode - always acknowledge the message otherwise it will requeue forever
        if (!$strict) {
            exit(self::STRICT_CODE_ACK);
        }

        // If in strict mode then test the return value from the consumer and return an appropriate code
        if ($consumerReturn === ConsumerInterface::MSG_REJECT) {
            exit(self::STRICT_CODE_REJECT);
        }

        exit(self::STRICT_CODE_ACK);
    }
}
