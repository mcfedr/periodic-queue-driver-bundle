<?php

namespace Mcfedr\PeriodicQueueDriverBundle\Command;

use Mcfedr\PeriodicQueueDriverBundle\Worker\PeriodicWorker;

class TestPeriodicDistributionCommand extends TestDistributionCommand
{
    public function configure()
    {
        parent::configure();
        $this->setName('test:distribution:periodic')
            ->setDescription('Emit samples for nextRun');
    }

    protected function job($period)
    {
        $currentTime = 0;

        return function () use (&$currentTime, $period) {
            timecop_freeze($currentTime);
            $currentTime = PeriodicWorker::nextRun($period)->getTimestamp();

            return $currentTime;
        };
    }
}
