<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250730202848 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tblProductData ADD decPrice DECIMAL(10,2) DEFAULT NULL');

        $this->addSql('ALTER TABLE tblProductData ADD intStock INT(10) UNSIGNED DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Rollback (removing new columns)
        $this->addSql('ALTER TABLE tblProductData DROP COLUMN decPrice');
        $this->addSql('ALTER TABLE tblProductData DROP COLUMN intStock');
    }
}
