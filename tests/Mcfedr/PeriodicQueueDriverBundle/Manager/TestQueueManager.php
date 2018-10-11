<?php

declare(strict_types=1);

namespace Mcfedr\PeriodicQueueDriverBundle\Manager;

use Mcfedr\QueueManagerBundle\Exception\NoSuchJobException;
use Mcfedr\QueueManagerBundle\Exception\WrongJobException;
use Mcfedr\QueueManagerBundle\Manager\QueueManager;
use Mcfedr\QueueManagerBundle\Queue\Job;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class TestQueueManager implements QueueManager, ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function __construct(array $options)
    {
        $this->container->get('logger')->info('construct', ['options' => $options]);
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
        $this->container->get('logger')->info('put', ['name' => $name, 'arguments' => $arguments, 'options' => $options]);
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
        $this->container->get('logger')->info('delete', ['job' => $job]);
    }
}
