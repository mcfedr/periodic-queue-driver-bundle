<?php

namespace Mcfedr\PeriodicQueueDriverBundle\Tests\Manager;

use Mcfedr\PeriodicQueueDriverBundle\Manager\PeriodicQueueManager;
use Mcfedr\QueueManagerBundle\Manager\QueueManagerRegistry;
use Mcfedr\QueueManagerBundle\Queue\Job;
use Symfony\Component\DependencyInjection\Container;

class PeriodicQueueManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PeriodicQueueManager
     */
    private $manager;

    /**
     * @var QueueManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $registry;

    /**
     * @var Container
     */
    private $container;

    public function setUp()
    {
        $this->manager = new PeriodicQueueManager([
            'delay_manager' => 'delay',
            'delay_manager_options' => [
                'delay_manager_option_a' => 'a',
            ],
        ]);

        $this->registry = $this->getMockBuilder(QueueManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->container = new Container();
        $this->container->set('mcfedr_queue_manager.registry', $this->registry);

        $this->manager->setContainer($this->container);
    }

    public function testPut()
    {
        $fakeJob = $this->getMockBuilder(Job::class)->getMock();
        $this->registry->expects($this->once())
            ->method('put')
            ->with('mcfedr_periodic_queue_driver.worker', [
                'name' => 'test_worker',
                'arguments' => [
                    'argument_a' => 'a',
                ],
                'period' => 3600,
                'delay_options' => [
                    'delay_manager_option_a' => 'a',
                ],
                'delay_manager' => 'delay',
            ], $this->callback(function ($options) {
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
            }), 'delay')
            ->willReturn($fakeJob);

        $job = $this->manager->put('test_worker', [
            'argument_a' => 'a',
        ], ['period' => 3600]);

        $this->assertEquals($fakeJob, $job);
    }

    public function testNoPeriod()
    {
        $fakeJob = $this->getMockBuilder(Job::class)->getMock();
        $this->registry->expects($this->once())
            ->method('put')
            ->with('test_worker', [
                'argument_a' => 'a',
            ], [
                'delay_manager_option_a' => 'a',
            ], 'delay')
            ->willReturn($fakeJob);

        $job = $this->manager->put('test_worker', [
            'argument_a' => 'a',
        ]);

        $this->assertEquals($fakeJob, $job);
    }

    /**
     * @expectedException \Mcfedr\QueueManagerBundle\Exception\WrongJobException
     */
    public function testDelete()
    {
        $this->manager->delete($this->getMockBuilder(Job::class)->getMock());
    }
}
