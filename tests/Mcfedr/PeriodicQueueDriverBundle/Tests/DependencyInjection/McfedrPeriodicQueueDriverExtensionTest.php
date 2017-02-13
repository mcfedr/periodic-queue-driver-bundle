<?php

namespace Mcfedr\PeriodicQueueDriverBundle\Tests\DependencyInjection;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class McfedrPeriodicQueueDriverExtensionTest extends WebTestCase
{
    public function testConfiguration()
    {
        $client = static::createClient();
        $this->assertTrue($client->getContainer()->has('mcfedr_queue_manager.periodic'));
    }
}
