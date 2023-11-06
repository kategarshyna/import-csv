<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231016082231 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '@Gedmo\Translatable functionality requires a translations table.'
            . ' Instead of using unsafe command `php bin/console doctrine:schema:update --force`'
            . ' Generate a migration based on App\Entity\Translations Entity.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE translations (id INT IDENTITY NOT NULL, locale NVARCHAR(8) NOT NULL, object_class NVARCHAR(255) NOT NULL, field NVARCHAR(32) NOT NULL, foreign_key NVARCHAR(64) NOT NULL, content VARCHAR(MAX), PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_TRANSLATION_OBJECT ON translations (locale, object_class, foreign_key)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_TRANSLATION_OBJECT ON translations');
        $this->addSql('DROP TABLE translations');
    }
}
