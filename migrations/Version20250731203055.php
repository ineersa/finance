<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250731203055 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, icon VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE source (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE statement (id INT AUTO_INCREMENT NOT NULL, filename VARCHAR(255) NOT NULL, uploaded_at DATETIME NOT NULL, processed_at DATETIME DEFAULT NULL, statement_date DATE DEFAULT NULL, source_id INT NOT NULL, INDEX IDX_C0DB5176953C1C61 (source_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE transaction (id INT AUTO_INCREMENT NOT NULL, date DATE NOT NULL, amount NUMERIC(10, 2) NOT NULL, amount_usd NUMERIC(10, 2) DEFAULT NULL, currency VARCHAR(3) NOT NULL, type VARCHAR(255) NOT NULL, statement_id INT NOT NULL, category_id INT NOT NULL, source_id INT NOT NULL, INDEX IDX_723705D1849CB65B (statement_id), INDEX IDX_723705D112469DE2 (category_id), INDEX IDX_723705D1953C1C61 (source_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE statement ADD CONSTRAINT FK_C0DB5176953C1C61 FOREIGN KEY (source_id) REFERENCES source (id)');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D1849CB65B FOREIGN KEY (statement_id) REFERENCES statement (id)');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D112469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D1953C1C61 FOREIGN KEY (source_id) REFERENCES source (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE statement DROP FOREIGN KEY FK_C0DB5176953C1C61');
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D1849CB65B');
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D112469DE2');
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D1953C1C61');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE source');
        $this->addSql('DROP TABLE statement');
        $this->addSql('DROP TABLE transaction');
    }
}
