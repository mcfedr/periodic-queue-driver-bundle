<?php

declare(strict_types=1);

namespace Mcfedr\PeriodicQueueDriverBundle\Command;

use Carbon\Carbon;
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
        $time = new Carbon();

        return function () use (&$time, $period) {
            Carbon::setTestNow($time);
            $time = Carbon::createFromTimestamp(PeriodicWorker::nextRun($period)->getTimestamp());

            return $time->timestamp;
        };
    }
}
