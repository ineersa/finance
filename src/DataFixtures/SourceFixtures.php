<?php

namespace App\DataFixtures;

use App\Entity\Source;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class SourceFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $source1 = new Source();
        $source1->setName('Bank Statement');
        $source1->setDescription('Statements from a primary bank account.');
        $source1->setAiInstruction('Extract transaction date, description, and amount from the bank statement.');
        $manager->persist($source1);

        $source2 = new Source();
        $source2->setName('Credit Card Statement');
        $source2->setDescription('Monthly credit card statements.');
        $source2->setAiInstruction('Parse the credit card statement for merchant name, transaction date, and amount. Categorize spending based on merchant.');
        $manager->persist($source2);

        $source3 = new Source();
        $source3->setName('Investment Portfolio Report');
        $source3->setDescription('Quarterly investment portfolio performance reports.');
        $source3->setAiInstruction('Extract investment gains, losses, and dividends from the report. Identify top-performing assets.');
        $manager->persist($source3);

        $manager->flush();
    }
}
