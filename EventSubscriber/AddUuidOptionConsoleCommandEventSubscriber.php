<?php

namespace Markup\JobQueueBundle\EventSubscriber;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Adds the `uuid` option to all console commands
 * @see http://php-and-symfony.matthiasnoback.nl/2013/11/symfony2-add-a-global-option-to-console-commands-and-generate-pid-file/
 * @see https://github.com/symfony/symfony/pull/15938
 */
class AddUuidOptionConsoleCommandEventSubscriber implements EventSubscriberInterface
{
    use CheckUsingSymfony28Trait;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ConsoleEvents::COMMAND => [
                ['onConsoleCommand', 100],
                ['bindInput', -9999998],
            ]
        ];
    }

    /**
     * @param ConsoleCommandEvent $event
     */
    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        $inputOption = new InputOption(
            'uuid',
            null,
            InputOption::VALUE_OPTIONAL,
            'The uuid of this console command. Should be unique for this console command at this time',
            null
        );

        //symfony 2.8+ has different behaviour available for adding options
        if ($this->isUsingAtLeastSymfony28()) {
            //for symfony 2.8 up
            $definition = $event->getCommand()->getDefinition();
            $definition->addOption($inputOption);
        } else {
            $inputDefinition = $event->getCommand()->getApplication()->getDefinition();
            $inputDefinition->addOption($inputOption);
        }
    }

    public function bindInput(ConsoleCommandEvent $event)
    {
        if (!$this->isUsingAtLeastSymfony28()) {
            return;
        }

        $event->getInput()->bind($event->getCommand()->getDefinition());
    }
}
