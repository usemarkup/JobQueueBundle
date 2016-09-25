<?php

namespace Markup\JobQueueBundle\EventSubscriber;

use Symfony\Component\HttpKernel\Kernel;

trait CheckUsingSymfony28Trait
{
    /**
     * @return bool
     */
    private function isUsingAtLeastSymfony28()
    {
        return version_compare(Kernel::VERSION, '2.8.0', '>=');
    }
}
