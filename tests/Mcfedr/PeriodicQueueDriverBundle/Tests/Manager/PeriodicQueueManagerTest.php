<?php

namespace Mcfedr\PeriodicQueueDriverBundle\Tests\Manager;

use Mcfedr\PeriodicQueueDriverBundle\Manager\PeriodicQueueManager;
use Mcfedr\PeriodicQueueDriverBundle\Queue\PeriodicJob;
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
        $pattern = '/[a-f0-9]{8}\-[a-f0-9]{4}\-[a-f0-9]{4}\-[a-f0-9]{4}\-[a-f0-9]{12}/';
        $fakeJob = $this->getMockBuilder(Job::class)->getMock();
        $this->registry->expects($this->once())
            ->method('put')
            ->with('mcfedr_periodic_queue_driver.worker',
                $this->callback(function ($arguments) use ($pattern) {
                    $this->assertCount(6, $arguments);
                    $this->assertArrayHasKey('name', $arguments);
                    $this->assertEquals('test_worker', $arguments['name']);
                    $this->assertArrayHasKey('arguments', $arguments);
                    $this->assertCount(1, $arguments['arguments']);

                    $this->assertArrayHasKey('argument_a', $arguments['arguments']);
                    $this->assertEquals('a', $arguments['arguments']['argument_a']);

                    $this->assertArrayHasKey('job_tokens', $arguments);
                    $this->assertCount(2, $arguments['job_tokens']);
                    $this->assertArrayHasKey('token', $arguments['job_tokens']);
                    $this->assertRegExp($pattern, $arguments['job_tokens']['token']);
                    $this->assertArrayHasKey('next_token', $arguments['job_tokens']);
                    $this->assertRegExp($pattern, $arguments['job_tokens']['next_token']);

                    $this->assertArrayHasKey('period', $arguments);
                    $this->assertEquals(3600, $arguments['period']);
                    $this->assertArrayHasKey('delay_options', $arguments);
                    $this->assertCount(1, $arguments['delay_options']);
                    $this->assertArrayHasKey('delay_manager_option_a', $arguments['delay_options']);
                    $this->assertEquals('a', $arguments['delay_options']['delay_manager_option_a']);
                    $this->assertArrayHasKey('delay_manager', $arguments);
                    $this->assertEquals('delay', $arguments['delay_manager']);

                    return true;
                }),
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
                }), 'delay')
            ->willReturn($fakeJob);

        $job = $this->manager->put('test_worker', [
            'argument_a' => 'a',
        ], ['period' => 3600]);

        $this->assertInstanceOf(PeriodicJob::class, $job);
        $this->assertRegExp($pattern, $job->getJobToken());
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
