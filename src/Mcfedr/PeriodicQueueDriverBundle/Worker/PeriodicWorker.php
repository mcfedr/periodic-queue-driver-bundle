<?php

namespace Mcfedr\PeriodicQueueDriverBundle\Worker;

use Carbon\Carbon;
use Mcfedr\PeriodicQueueDriverBundle\Queue\InternalPeriodicJob;
use Mcfedr\PeriodicQueueDriverBundle\Queue\PeriodicJob;
use Mcfedr\QueueManagerBundle\Exception\UnrecoverableJobException;
use Mcfedr\QueueManagerBundle\Manager\QueueManagerRegistry;
use Mcfedr\QueueManagerBundle\Queue\Job;
use Mcfedr\QueueManagerBundle\Queue\Worker;
use Mcfedr\QueueManagerBundle\Runner\JobExecutor;
use Ramsey\Uuid\Uuid;

class PeriodicWorker implements Worker
{
    /**
     * @var QueueManagerRegistry
     */
    private $queueManagerRegistry;

    /**
     * @var JobExecutor
     */
    private $jobExecutor;

    public function __construct(QueueManagerRegistry $queueManagerRegistry, JobExecutor $jobExecutor)
    {
        $this->queueManagerRegistry = $queueManagerRegistry;
        $this->jobExecutor = $jobExecutor;
    }

    /**
     * Called to start the queued task.
     *
     * @param array $arguments
     * @return Job
     *
     * @throws \Exception
     * @throws UnrecoverableJobException
     */
    public function execute(array $arguments)
    {
        if (!isset($arguments['name']) || !isset($arguments['arguments']) || !isset($arguments['period']) || !isset($arguments['delay_options']) || !isset($arguments['delay_manager'])) {
            throw new UnrecoverableJobException('Missing arguments for periodic job');
        }

        $job = new PeriodicJob($arguments['name'], $arguments['arguments'], $arguments['job_tokens']);
        $this->jobExecutor->executeJob($job);

        $nextJob = $job->generateNextJob();
        $arguments['job_tokens'] = $nextJob->getJobTokens();

        $nextRun = self::nextRun($arguments['period']);
        
        return $this->queueManagerRegistry->put('mcfedr_periodic_queue_driver.worker', $arguments, array_merge([
            'time' => $nextRun,
        ], $arguments['delay_options']), $arguments['delay_manager']);
    }

    /**
     * @param int $periodLength seconds
     *
     * @return \DateTime
     */
    public static function nextRun($periodLength)
    {
        list($startOfNextPeriod, $endOfNextPeriod) = self::nextPeriod($periodLength);
        $time = random_int($startOfNextPeriod, $endOfNextPeriod);

        return new Carbon("@$time");
    }

    /**
     * @param int $periodLength seconds
     *
     * @return int[] 0 is start and 1 is the end as timestamps
     */
    public static function nextPeriod($periodLength)
    {
        $now = Carbon::now()->timestamp;
        $startOfNextPeriod = ceil($now / $periodLength) * $periodLength;

        return [$startOfNextPeriod + 1, $startOfNextPeriod + $periodLength];
    }
}
