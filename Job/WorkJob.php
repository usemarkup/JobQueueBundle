<?php

namespace Markup\JobQueueBundle\Job;

use Markup\JobQueueBundle\Model\Job;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A job that simulates work - for testing your queue
 */
class WorkJob extends Job
{
    public function validate()
    {
        if (!isset($this->args['units'])) {
            throw new InvalidJobArgumentException('`units` must be set');
        }
        if (!isset($this->args['complexity'])) {
            throw new InvalidJobArgumentException('`complexity` must be set');
        }
    }

    public function run(ContainerInterface $container)
    {
        $garbage = '';
        $completed = 0;
        while ($completed < $this->args['units']) {
            $garbage .= password_hash(openssl_random_pseudo_bytes($this->args['complexity']), PASSWORD_BCRYPT);
            $completed++;
        }
    }
}
