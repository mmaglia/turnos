<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200605144133 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql("UPDATE turno SET notebook = FALSE, zoom = FALSE");
        $this->addSql("UPDATE turno_rechazado SET notebook = FALSE, zoom = FALSE");

        $this->addSql("ALTER TABLE turno ALTER COLUMN notebook SET DEFAULT FALSE");
        $this->addSql("ALTER TABLE turno ALTER COLUMN zoom SET DEFAULT FALSE");
        $this->addSql("ALTER TABLE turno_rechazado ALTER COLUMN notebook SET DEFAULT FALSE");
        $this->addSql("ALTER TABLE turno_rechazado ALTER COLUMN zoom SET DEFAULT FALSE");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE turno_rechazado ALTER notebook DROP NOT NULL');
        $this->addSql('ALTER TABLE turno_rechazado ALTER zoom DROP NOT NULL');
        $this->addSql('ALTER TABLE turno ALTER notebook DROP NOT NULL');
        $this->addSql('ALTER TABLE turno ALTER zoom DROP NOT NULL');
    }
}
