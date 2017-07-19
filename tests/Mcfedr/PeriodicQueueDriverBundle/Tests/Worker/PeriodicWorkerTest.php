<?php

namespace Mcfedr\PeriodicQueueDriverBundle\Tests\Worker;

use Mcfedr\PeriodicQueueDriverBundle\Worker\PeriodicWorker;
use Mcfedr\QueueManagerBundle\Exception\UnrecoverableJobException;
use Mcfedr\QueueManagerBundle\Manager\QueueManagerRegistry;
use Mcfedr\QueueManagerBundle\Queue\Job;
use Mcfedr\QueueManagerBundle\Runner\JobExecutor;

class PeriodicWorkerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PeriodicWorker
     */
    private $worker;

    /**
     * @var QueueManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $registry;

    /**
     * @var JobExecutor|\PHPUnit_Framework_MockObject_MockObject
     */
    private $executor;

    public function setUp()
    {
        $this->registry = $this->getMockBuilder(QueueManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->executor = $this->getMockBuilder(JobExecutor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->worker = new PeriodicWorker($this->registry, $this->executor);
    }

    public function tearDown()
    {
        if (function_exists('timecop_return')) {
            timecop_return();
        }
    }

    public function testExecute()
    {
        $this->executor->expects($this->once())
            ->method('executeJob')
            ->with($this->callback(function ($job) {
                if (!$job instanceof Job) {
                    return false;
                }
                if ($job->getName() != 'test_worker') {
                    return false;
                }
                if ($job->getArguments() != [
                        'argument_a' => 'a',
                    ]) {
                    return false;
                }

                return true;
            }));

        $this->registry->expects($this->once())
            ->method('put')
            ->withConsecutive([
                'mcfedr_periodic_queue_driver.worker',
                [
                    'name' => 'test_worker',
                    'arguments' => [
                        'argument_a' => 'a',
                    ],
                    'period' => 3600,
                    'delay_options' => [
                        'delay_manager_option_a' => 'a',
                    ],
                    'delay_manager' => 'delay',
                ],
                $this->callback(function ($options) {
                    if (!is_array($options)) {
                        return false;
                    }
                    if (!isset($options['delay_manager_option_a']) || $options['delay_manager_option_a'] != 'a') {
                        return false;
                    }
                    if (!isset($options['time']) || !$options['time'] instanceof \DateTime) {
                        return false;
                    }

                    return true;
                }),
                'delay',
            ])
            ->willReturnOnConsecutiveCalls($this->getMockBuilder(Job::class)->getMock());

        $this->worker->execute([
            'name' => 'test_worker',
            'arguments' => [
                'argument_a' => 'a',
            ],
            'period' => 3600,
            'delay_options' => [
                'delay_manager_option_a' => 'a',
            ],
            'delay_manager' => 'delay',
        ]);
    }

    /**
     * @expectedException \Mcfedr\QueueManagerBundle\Exception\UnrecoverableJobException
     */
    public function testExecuteThrows()
    {
        $this->executor->expects($this->once())
            ->method('executeJob')
            ->with($this->callback(function ($job) {
                if (!$job instanceof Job) {
                    return false;
                }
                if ($job->getName() != 'test_worker') {
                    return false;
                }
                if ($job->getArguments() != [
                        'argument_a' => 'a',
                    ]) {
                    return false;
                }

                return true;
            }))
            ->willThrowException(new UnrecoverableJobException('Fail'));

        $this->registry->expects($this->never())
            ->method('put');

        $this->worker->execute([
            'name' => 'test_worker',
            'arguments' => [
                'argument_a' => 'a',
            ],
            'period' => 3600,
            'delay_options' => [
                'delay_manager_option_a' => 'a',
            ],
            'delay_manager' => 'delay',
        ]);
    }

    /**
     * @dataProvider length
     */
    public function testNextRun($length)
    {
        if (function_exists('timecop_freeze')) {
            // Make sure time doesnt move so seperate calls to nextPeriod give the same answer
            timecop_freeze();
        }
        list($startOfNextPeriod, $endOfNextPeriod) = PeriodicWorker::nextPeriod($length);
        $start = new \DateTime("@$startOfNextPeriod");
        $end = new \DateTime("@$endOfNextPeriod");

        $test = PeriodicWorker::nextRun($length);

        $this->assertGreaterThan($start, $test);
        $this->assertLessThan($end, $test);
    }

    /**
     * @dataProvider length
     */
    public function testNextPeriod($length)
    {
        if (!function_exists('timecop_freeze')) {
            return;
        }

        $time = gmmktime(12, 0, 0);
        timecop_freeze($time);

        list($startOfNextPeriod, $endOfNextPeriod) = PeriodicWorker::nextPeriod($length);

        $this->assertEquals($time + 1, $startOfNextPeriod);
        $this->assertEquals($time + $length, $endOfNextPeriod);

        timecop_freeze($time + 1);

        list($startOfNextPeriod, $endOfNextPeriod) = PeriodicWorker::nextPeriod($length);

        $this->assertEquals($time + $length + 1, $startOfNextPeriod);
        $this->assertEquals($time + $length + $length, $endOfNextPeriod);

        timecop_freeze($time + 15);

        list($startOfNextPeriod, $endOfNextPeriod) = PeriodicWorker::nextPeriod($length);

        $this->assertEquals($time + $length + 1, $startOfNextPeriod);
        $this->assertEquals($time + $length + $length, $endOfNextPeriod);
    }

    public function length()
    {
        return [
            [3600],
            [100],
            [50],
        ];
    }
}
