<?php

namespace Markup\JobQueueBundle\Job;

use Markup\JobQueueBundle\Exception\InvalidJobArgumentException;
use Markup\JobQueueBundle\Model\Job;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * A job that simulates work - for testing message consumption rates
 */
class WorkJob extends Job
{
    /**
     * {@inheritdoc}
     */
    public function validate()
    {
        if (!isset($this->args['units'])) {
            throw new InvalidJobArgumentException('`units` must be set');
        }
        if (!isset($this->args['complexity'])) {
            throw new InvalidJobArgumentException('`complexity` must be set');
        }
        if (!is_numeric($this->args['units']) || !is_numeric($this->args['complexity'])) {
            throw new InvalidJobArgumentException('`units` & `complexity` must both be integers');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function run(ParameterBagInterface $parameterBag): string
    {
        $garbage = '';
        $completed = 0;
        while ($completed < $this->args['units']) {
            $garbage .= password_hash(openssl_random_pseudo_bytes($this->args['complexity']), PASSWORD_BCRYPT);
            $completed++;
        }

        return '';
    }
}
