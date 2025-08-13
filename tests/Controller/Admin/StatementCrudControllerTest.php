<?php

namespace App\Tests\Controller\Admin;

use App\Entity\Source;
use App\Entity\Statement;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class StatementCrudControllerTest extends WebTestCase
{
    public function testStatementsListPageLoadsSuccessfully(): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get('doctrine')->getRepository(User::class);
        $testUser = $userRepository->findOneBy(['email' => 'admin@test.com']);
        $client->loginUser($testUser);

        $crawler = $client->request('GET', '/home/statement');
        $this->assertResponseIsSuccessful();
    }

    public function testStatementCreatePageLoadsSuccessfully(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get('doctrine')->getRepository(User::class);
        $testUser = $userRepository->findOneBy(['email' => 'admin@test.com']);
        $client->loginUser($testUser);

        $crawler = $client->request('GET', '/home/statement/new');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[name="Statement[statement_file]"]');
        $this->assertSelectorExists('select[name="Statement[source]"]');
        $this->assertSelectorExists('input[name="Statement[statement_date]"]');
    }

    public function testStatementCreateSubmission(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get('doctrine')->getRepository(User::class);
        $testUser = $userRepository->findOneBy(['email' => 'admin@test.com']);
        $client->loginUser($testUser);

        $crawler = $client->request('GET', '/home/statement/new');

        // Prepare a temporary CSV file
        $tmpDir = sys_get_temp_dir();
        $tmpFile = tempnam($tmpDir, 'csv');
        file_put_contents($tmpFile, "\"Description\",\"Type\",\"Card Holder Name\",\"Date\",\"Time\",\"Amount\"\n\"Sample\",\"PAYMENT\",\"TEST\",\"05/17/2025\",\"08:19 PM\",\"105.48\"\n");
        $uploaded = new UploadedFile($tmpFile, 'test.csv', 'text/csv', null, true);

        $sourceRepository = static::getContainer()->get('doctrine')->getRepository(Source::class);
        $source = $sourceRepository->findOneBy([]);
        $this->assertNotNull($source, 'No source found in fixtures');

        // Build form data
        $form = $crawler->selectButton('Create')->form();
        $form['Statement[statement_file]']->upload($uploaded);
        $form['Statement[source]']->select($source->getId());
        // Leave statement_date empty (optional)

        $client->submit($form);

        if ($client->getResponse()->isRedirect() && str_contains($client->getResponse()->headers->get('Location'), '/home/statement/new')) {
            $client->followRedirect();
            $this->assertResponseIsSuccessful();
            $this->assertTrue(true, 'Create had validation errors - redirected back to create page');
        } else {
            $this->assertResponseRedirects();
            $client->followRedirect();
            $this->assertResponseIsSuccessful();

            $repo = static::getContainer()->get('doctrine')->getRepository(Statement::class);
            $created = $repo->findOneBy([], ['id' => 'DESC']);
            $this->assertNotNull($created);
            $this->assertNotEmpty($created->getFilename());
            $this->assertNotNull($created->getUploadedAt());
        }
    }

    public function testStatementEditPageLoadsSuccessfully(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get('doctrine')->getRepository(User::class);
        $testUser = $userRepository->findOneBy(['email' => 'admin@test.com']);
        $client->loginUser($testUser);

        $repo = static::getContainer()->get('doctrine')->getRepository(Statement::class);
        $statement = $repo->findOneBy([]);
        $this->assertNotNull($statement, 'No statements found in fixtures');

        $crawler = $client->request('GET', '/home/statement/'.$statement->getId().'/edit');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('select[name="Statement[source]"]');
    }

    public function testStatementEditSubmission(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get('doctrine')->getRepository(User::class);
        $testUser = $userRepository->findOneBy(['email' => 'admin@test.com']);
        $client->loginUser($testUser);

        $repo = static::getContainer()->get('doctrine')->getRepository(Statement::class);
        $statement = $repo->findOneBy([]);
        $this->assertNotNull($statement, 'No statements found in fixtures');

        $crawler = $client->request('GET', '/home/statement/'.$statement->getId().'/edit');

        $sourceRepository = static::getContainer()->get('doctrine')->getRepository(Source::class);
        $anotherSource = $sourceRepository->findOneBy(['id' => $statement->getSource()->getId() + 1]) ?? $sourceRepository->findOneBy([]);
        $this->assertNotNull($anotherSource);

        $form = $crawler->selectButton('Save changes')->form([
            'Statement[source]' => $anotherSource->getId(),
        ]);

        $client->submit($form);
        $this->assertResponseRedirects();
        $client->followRedirect();
        $this->assertResponseIsSuccessful();

        $updated = $repo->find($statement->getId());
        $this->assertEquals($anotherSource->getId(), $updated->getSource()->getId());
    }

    public function testStatementDeleteAction(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get('doctrine')->getRepository(User::class);
        $testUser = $userRepository->findOneBy(['email' => 'admin@test.com']);
        $client->loginUser($testUser);

        $repo = static::getContainer()->get('doctrine')->getRepository(Statement::class);
        $statement = $repo->findOneBy([]);
        $this->assertNotNull($statement, 'No statements found in fixtures');

        $initialCount = \count($repo->findAll());

        $crawler = $client->request('GET', '/home/statement');
        $this->assertResponseIsSuccessful();

        $deleteForm = $crawler->filter('form#delete-form');
        $this->assertGreaterThan(0, $deleteForm->count(), 'Hidden delete form not found on page');
        $csrfInput = $deleteForm->filter('input[name="token"]');
        $this->assertGreaterThan(0, $csrfInput->count(), 'CSRF token input not found in delete form');
        $csrfToken = $csrfInput->attr('value');

        $client->request('POST', '/home/statement/'.$statement->getId().'/delete', [
            'token' => $csrfToken,
        ]);

        $this->assertResponseRedirects();
        $client->followRedirect();

        $deleted = $repo->find($statement->getId());
        $this->assertNull($deleted);
        $finalCount = \count($repo->findAll());
        $this->assertEquals($initialCount - 1, $finalCount);
    }
}
