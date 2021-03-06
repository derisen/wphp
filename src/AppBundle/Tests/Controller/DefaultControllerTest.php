<?php

namespace AppBundle\Tests\AppBundle\Controller;

use Nines\UtilBundle\Tests\Util\BaseTestCase;

class DefaultControllerTest extends BaseTestCase
{
    public function testIndex()
    {
        $this->markTestSkipped('Cannot test this page with SQLite.');
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains('Welcome to Symfony', $crawler->filter('#container h1')->text());
    }
}
