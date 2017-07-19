<?php

namespace Mcfedr\PeriodicQueueDriverBundle\Manager;

use Mcfedr\PeriodicQueueDriverBundle\Worker\PeriodicWorker;
use Mcfedr\QueueManagerBundle\Exception\NoSuchJobException;
use Mcfedr\QueueManagerBundle\Exception\WrongJobException;
use Mcfedr\QueueManagerBundle\Manager\QueueManager;
use Mcfedr\QueueManagerBundle\Queue\Job;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class PeriodicQueueManager implements QueueManager, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var string
     */
    private $delayManager;

    /**
     * @var array
     */
    private $delayManagerOptions = [];

    /**
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->delayManager = $options['delay_manager'];
        $this->delayManagerOptions = $options['delay_manager_options'];
    }

    /**
     * Put a new job on a queue.
     *
     * @param string $name      The service name of the worker that implements {@link \Mcfedr\QueueManagerBundle\Queue\Worker}
     * @param array  $arguments Arguments to pass to execute - must be json serializable
     * @param array  $options   Options for creating the job - these depend on the driver used
     *
     * @return Job
     */
    public function put($name, array $arguments = [], array $options = [])
    {
        if (array_key_exists('delay_manager_options', $options)) {
            $jobOptions = array_merge($this->delayManagerOptions, $options['delay_manager_options']);
        } else {
            $jobOptions = array_merge($this->delayManagerOptions, array_diff_key($options, ['period' => 1, 'time' => 1, 'delay' => 1]));
        }

        if (array_key_exists('delay_manager', $options)) {
            $jobManager = $options['delay_manager'];
        } else {
            $jobManager = $this->delayManager;
        }

        if (isset($options['period'])) {
            $period = $options['period'];
        } else {
            return $this->container->get('mcfedr_queue_manager.registry')->put($name, $arguments, $jobOptions, $jobManager);
        }

        return $this->container->get('mcfedr_queue_manager.registry')->put('mcfedr_periodic_queue_driver.worker', [
            'name' => $name,
            'arguments' => $arguments,
            'period' => $period,
            'delay_options' => $jobOptions,
            'delay_manager' => $jobManager,
        ], array_merge([
            'time' => PeriodicWorker::nextRun($period),
        ], $jobOptions), $jobManager);
    }

    /**
     * Remove a job from the queue.
     *
     * @param $job Job
     *
     * @throws WrongJobException  When this manager doesn't know how to delete the given job
     * @throws NoSuchJobException When this manager is unable to delete the given job
     */
    public function delete(Job $job)
    {
        throw new WrongJobException('Periodic queue manager cannot delete jobs');
    }
}
