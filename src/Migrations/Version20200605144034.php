<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200605144034 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE turno_rechazado ADD notebook BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE turno_rechazado ADD zoom BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE turno ADD notebook BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE turno ADD zoom BOOLEAN DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE turno DROP notebook');
        $this->addSql('ALTER TABLE turno DROP zoom');
        $this->addSql('ALTER TABLE turno_rechazado DROP notebook');
        $this->addSql('ALTER TABLE turno_rechazado DROP zoom');
    }
}
