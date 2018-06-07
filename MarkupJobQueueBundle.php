<?php

namespace Markup\JobQueueBundle;

use Symfony\Component\Console\Application;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Kernel;

class MarkupJobQueueBundle extends Bundle
{
    public function registerCommands(Application $application)
    {
        if (version_compare(Kernel::VERSION, '2.7', '>=')) {
            return;
        }
        parent::registerCommands($application);
    }

}
