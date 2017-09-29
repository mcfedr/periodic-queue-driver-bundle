<?php

namespace Mcfedr\PeriodicQueueDriverBundle\Queue;

use Mcfedr\QueueManagerBundle\Queue\AbstractJob;
use Ramsey\Uuid\Uuid;

class PeriodicJob extends AbstractJob
{
    /**
     * @var string
     */
    private $jobToken;

    /**
     * @var string
     */
    private $nextJobToken;


    /**
     * Get JobToken.
     *
     * @return string
     */
    public function getJobToken()
    {
        return $this->jobToken;
    }

    /**
     * Get NextJobToken.
     *
     * @return string
     */
    public function getNextJobToken()
    {
        return $this->nextJobToken;
    }

    public function generateTokens()
    {
        $this->jobToken = $this->nextJobToken = Uuid::uuid4()->toString();
    }

    public function updateTokens()
    {
        $this->jobToken = $this->nextJobToken;
        $this->nextJobToken = Uuid::uuid4()->toString();
    }
}
