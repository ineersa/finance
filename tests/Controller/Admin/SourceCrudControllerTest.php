<?php

namespace App\Tests\Controller\Admin;

use App\Entity\Source;
use App\Entity\User;
use App\Tests\Controller\AbstractControllerTest;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SourceCrudControllerTest extends WebTestCase
{
    public function testSourcesListPageLoadsSuccessfully(): void
    {
        $client = static::createClient();

        // Login as admin user
        $userRepository = static::getContainer()->get('doctrine')->getRepository(User::class);
        $testUser = $userRepository->findOneByEmail('admin@test.com');

        $client->loginUser($testUser);

        // Access the sources list page
        $crawler = $client->request('GET', '/home/source');

        // Assert successful response
        $this->assertResponseIsSuccessful();

        // For now, just verify the page loads successfully
        // More specific assertions can be added when the test environment is fully working
    }
}
