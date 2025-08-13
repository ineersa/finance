<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Statement;
use App\Entity\Transaction;
use App\Enum\TransactionTypeEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class TransactionFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Ensure we have a statement to attach to
        $statementRepo = $manager->getRepository(Statement::class);
        $statement = $statementRepo->findOneBy([]);
        if (!$statement) {
            // No statements yet? Create a minimal one referencing existing source via StatementFixtures normally
            return; // rely on StatementFixtures to create first; in test loading order, it exists
        }

        // Pick default category (Other Transactions) or any existing
        $categoryRepo = $manager->getRepository(Category::class);
        $category = $categoryRepo->findOneBy(['name' => 'Other Transactions']) ?? $categoryRepo->findOneBy([]);

        // Create a couple of sample transactions
        for ($i = 1; $i <= 2; ++$i) {
            $t = new Transaction();
            $t->setStatement($statement);
            $t->setDate(new \DateTimeImmutable('now -'.$i.' days'));
            $t->setAmount((string) (100 * $i + 0.50));
            $t->setCurrency('USD');
            $t->setAmountUsd((string) (100 * $i + 0.50));
            $t->setType(0 === $i % 2 ? TransactionTypeEnum::Credit : TransactionTypeEnum::Debit);
            if ($category) {
                $t->setCategory($category);
            }
            // Set source from statement
            $t->setSource($statement->getSource());

            $manager->persist($t);
        }

        $manager->flush();
    }
}
