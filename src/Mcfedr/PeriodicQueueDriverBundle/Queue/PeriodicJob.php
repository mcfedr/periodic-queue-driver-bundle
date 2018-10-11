<?php

declare(strict_types=1);

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
     */
    public static function generateJobTokens(): array
    {
        return [
            'token' => Uuid::uuid4()->toString(),
            'next_token' => Uuid::uuid4()->toString(),
        ];
    }

    /**
     * @return string
     */
    public function getJobToken(): string
    {
        return $this->jobTokens['token'];
    }

    public function getArguments(): array
    {
        return array_merge(parent::getArguments(), ['job_tokens' => $this->getJobTokens()]);
    }

    /**
     * Get the next run of this job.
     */
    public function generateNextJob(): PeriodicJob
    {
        $tokens = $this->getJobTokens();
        $tokens['token'] = $tokens['next_token'];
        $tokens['next_token'] = Uuid::uuid4()->toString();

        return new self($this->getName(), $this->getArguments(), $tokens);
    }

    public function getJobTokens(): array
    {
        return $this->jobTokens;
    }
}
