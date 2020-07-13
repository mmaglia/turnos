<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200629215725 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE oficina ADD auto_extend BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE oficina ADD auto_gestion BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE turno ALTER notebook DROP DEFAULT');
        $this->addSql('ALTER TABLE turno ALTER zoom DROP DEFAULT');
        $this->addSql('ALTER TABLE turno_rechazado ALTER notebook DROP DEFAULT');
        $this->addSql('ALTER TABLE turno_rechazado ALTER zoom DROP DEFAULT');
        if ($_ENV['SISTEMA_TURNOS_WEB']) {
            $this->addSql("UPDATE oficina SET auto_extend = TRUE WHERE id > 2 and id <> 225 and oficina not ilike '%Token%'");
            $this->addSql("UPDATE oficina SET auto_gestion = TRUE WHERE oficina ilike '%Token%'");
        }
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE turno ALTER notebook SET DEFAULT \'false\'');
        $this->addSql('ALTER TABLE turno ALTER zoom SET DEFAULT \'false\'');
        $this->addSql('ALTER TABLE turno_rechazado ALTER notebook SET DEFAULT \'false\'');
        $this->addSql('ALTER TABLE turno_rechazado ALTER zoom SET DEFAULT \'false\'');
        $this->addSql('ALTER TABLE oficina DROP auto_extend');
        $this->addSql('ALTER TABLE oficina DROP auto_gestion');
    }
}
