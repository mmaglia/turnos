<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200508120827 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE turno_rechazado_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE turno_rechazado (id INT NOT NULL, persona_id INT DEFAULT NULL, oficina_id INT DEFAULT NULL, fecha_hora_rechazo TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, fecha_hora_turno TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, motivo VARCHAR(255) DEFAULT NULL, motivo_rechazo VARCHAR(255) DEFAULT NULL, email_enviado BOOLEAN DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_88395B59F5F88DB9 ON turno_rechazado (persona_id)');
        $this->addSql('CREATE INDEX IDX_88395B598A8639B7 ON turno_rechazado (oficina_id)');
        $this->addSql('ALTER TABLE turno_rechazado ADD CONSTRAINT FK_88395B59F5F88DB9 FOREIGN KEY (persona_id) REFERENCES persona (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE turno_rechazado ADD CONSTRAINT FK_88395B598A8639B7 FOREIGN KEY (oficina_id) REFERENCES oficina (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE turno ALTER estado SET NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE turno_rechazado_id_seq CASCADE');
        $this->addSql('DROP TABLE turno_rechazado');
        $this->addSql('ALTER TABLE turno ALTER estado DROP NOT NULL');
    }
}
