<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231010134855 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create EClassTree table to store EClass code, name, version, and its hierarchy.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE EClassTree (
    Code INT IDENTITY(1,1) PRIMARY KEY NOT NULL,
    Version NVARCHAR(50) NOT NULL,
    PreferredName NVARCHAR(255) NOT NULL,
    ParentFK INT,
    CONSTRAINT FK_A3C7D1E5B2F8G6H FOREIGN KEY (ParentFK) REFERENCES EClassTree(Code)
                        )');
        $this->addSql('CREATE INDEX IDX_E_CLASS_NAME ON EClassTree (PreferredName)');
        $this->addSql('CREATE INDEX IDX_E_CLASS_PARENT ON EClassTree (ParentFK)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE EClassTree DROP CONSTRAINT FK_A3C7D1E5B2F8G6H');
        $this->addSql('DROP INDEX IDX_E_CLASS_NAME ON EClassTree');
        $this->addSql('DROP INDEX IDX_E_CLASS_PARENT ON EClassTree');
        $this->addSql('ALTER TABLE EClassTree DROP COLUMN ParentFK');
        $this->addSql('DROP TABLE EClassTree');
    }
}
