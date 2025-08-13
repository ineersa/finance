<?php

namespace App\DataFixtures;

use App\Entity\Source;
use App\Entity\Statement;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Filesystem\Filesystem;

class StatementFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $sourceRepo = $manager->getRepository(Source::class);
        $source = $sourceRepo->findOneBy([]);
        if (!$source) {
            // If no source exists yet (in case fixtures order), create a basic one
            $source = new Source();
            $source->setName('Default Source');
            $source->setDescription('Autocreated source for statement fixtures');
            $manager->persist($source);
            $manager->flush();
        }

        $publicFixturePath = __DIR__.'/../../public/storage/fixtures/statement_fixture.csv';
        $storagePath = $_ENV['STATEMENTS_STORAGE_PATH'] ?? '/var/www/storage/';
        $filesystem = new Filesystem();
        if (!$filesystem->exists($storagePath)) {
            $filesystem->mkdir($storagePath, 0755);
        }

        $targetFilename = 'fixture-statement-'.bin2hex(random_bytes(4)).'.csv';
        $filesystem->copy($publicFixturePath, rtrim($storagePath, '/').'/'.$targetFilename, true);

        $statement = new Statement();
        $statement->setFilename($targetFilename);
        $statement->setUploadedAt(new DateTimeImmutable());
        $statement->setSource($source);
        // Optional statement_date left null by default

        $manager->persist($statement);
        $manager->flush();
    }
}
