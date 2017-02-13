<?php

namespace Mcfedr\PeriodicQueueDriverBundle\Command;

class TestRandAddDistributionCommand extends TestDistributionCommand
{
    public function configure()
    {
        parent::configure();
        $this->setName('test:distribution:rand-add')
            ->setDescription('Emit samples for adding rand');
    }

    protected function job($period)
    {
        $currentTime = 0;

        return function () use (&$currentTime, $period) {
            $currentTime += mt_rand($period, $period * 2);

            return $currentTime;
        };
    }
}
