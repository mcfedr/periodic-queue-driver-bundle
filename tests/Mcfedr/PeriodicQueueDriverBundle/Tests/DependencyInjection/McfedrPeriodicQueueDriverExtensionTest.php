<?php

namespace Mcfedr\PeriodicQueueDriverBundle\Tests\DependencyInjection;

use Mcfedr\PeriodicQueueDriverBundle\Manager\PeriodicQueueManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class McfedrPeriodicQueueDriverExtensionTest extends WebTestCase
{
    public function testConfiguration()
    {
        $client = static::createClient();
        $this->assertTrue($client->getContainer()->has(PeriodicQueueManager::class));
        $this->assertTrue($client->getContainer()->has('mcfedr_queue_manager.periodic'));
    }
}
