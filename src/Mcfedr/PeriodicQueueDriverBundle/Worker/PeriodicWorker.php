<?php

namespace Mcfedr\PeriodicQueueDriverBundle\Worker;

use Mcfedr\PeriodicQueueDriverBundle\Queue\PeriodicJob;
use Mcfedr\QueueManagerBundle\Exception\UnrecoverableJobException;
use Mcfedr\QueueManagerBundle\Manager\QueueManagerRegistry;
use Mcfedr\QueueManagerBundle\Queue\Worker;
use Mcfedr\QueueManagerBundle\Runner\JobExecutor;

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
     *
     * @throws \Exception
     * @throws UnrecoverableJobException
     */
    public function execute(array $arguments)
    {
        if (!isset($arguments['name']) || !isset($arguments['arguments']) || !isset($arguments['period']) || !isset($arguments['delay_options']) || !isset($arguments['delay_manager'])) {
            throw new UnrecoverableJobException('Missing arguments for periodic job');
        }

        $job = new PeriodicJob($arguments['name'], $arguments['arguments']);
        $this->jobExecutor->executeJob($job);

        $nextRun = self::nextRun($arguments['period']);
        $this->queueManagerRegistry->put('mcfedr_periodic_queue_driver.worker', $arguments, array_merge([
            'time' => $nextRun
        ], $arguments['delay_options']), $arguments['delay_manager']);
    }

    public static function nextRun($periodLength)
    {
        list($startOfNextPeriod, $endOfNextPeriod) = self::nextPeriod($periodLength);
        $time = random_int($startOfNextPeriod, $endOfNextPeriod);

        return new \DateTime("@$time");
    }

    public static function nextPeriod($periodLength)
    {
        $now = time();
        $startOfNextPeriod = ceil($now / $periodLength) * $periodLength;

        return [$startOfNextPeriod + 1, $startOfNextPeriod + $periodLength];
    }
}
