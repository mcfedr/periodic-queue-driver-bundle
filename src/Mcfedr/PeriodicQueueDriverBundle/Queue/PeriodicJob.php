<?php

namespace Mcfedr\PeriodicQueueDriverBundle\Queue;

use Mcfedr\QueueManagerBundle\Queue\AbstractJob;
use Ramsey\Uuid\Uuid;

class PeriodicJob extends AbstractJob
{
    /**
     * @var array
     */
    private $jobTokens;

    /**
     * @param string $name
     * @param array  $arguments
     * @param array  $jobTokens
     */
    public function __construct($name, array $arguments, array $jobTokens)
    {
        parent::__construct($name, $arguments);
        $this->jobTokens = $jobTokens;
    }

    /**
     * Generate tokens for a new job.
     *
     * @return array
     */
    public static function generateJobTokens()
    {
        return [
            'token' => Uuid::uuid4()->toString(),
            'next_token' => Uuid::uuid4()->toString(),
        ];
    }

    /**
     * @return string
     */
    public function getJobToken()
    {
        return $this->jobTokens['token'];
    }

    public function getArguments()
    {
        return array_merge(parent::getArguments(), ['job_tokens' => $this->getJobTokens()]);
    }

    /**
     * Get the next run of this job.
     *
     * @return PeriodicJob
     */
    public function generateNextJob()
    {
        $tokens = $this->getJobTokens();
        $tokens['token'] = $tokens['next_token'];
        $tokens['next_token'] = Uuid::uuid4()->toString();

        return new self($this->getName(), $this->getArguments(), $tokens);
    }

    /**
     * @return array
     */
    public function getJobTokens()
    {
        return $this->jobTokens;
    }
}
