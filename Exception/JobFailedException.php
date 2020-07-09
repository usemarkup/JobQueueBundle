<?php

namespace Markup\JobQueueBundle\Exception;

class JobFailedException extends \Exception
{
    /**
     * @var null
     */
    private $exitCode;

    /**
     * JobFailedException constructor.
     *
     * @param string         $message
     * @param null           $exitCode
     * @param int            $code
     * @param \Throwable|null $previous
     */
    public function __construct(
        $message = "",
        $exitCode = null,
        $code = 0,
        \Throwable $previous = null
    ) {
        $this->exitCode = $exitCode;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return null
     */
    public function getExitCode()
    {
        return $this->exitCode;
    }
}
