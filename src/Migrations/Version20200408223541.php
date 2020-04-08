<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200408223541 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE localidad_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE oficina_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE turno_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE persona_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE localidad (id INT NOT NULL, localidad VARCHAR(80) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE oficina (id INT NOT NULL, localidad_id INT DEFAULT NULL, oficina VARCHAR(120) NOT NULL, hora_inicio_atencion TIME(0) WITHOUT TIME ZONE NOT NULL, hora_fin_atencion TIME(0) WITHOUT TIME ZONE NOT NULL, frecuencia_atencion SMALLINT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_543A5AC67707C89 ON oficina (localidad_id)');
        $this->addSql('CREATE TABLE turno (id INT NOT NULL, persona_id INT DEFAULT NULL, oficina_id INT DEFAULT NULL, fecha_hora TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, motivo VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_E7976762F5F88DB9 ON turno (persona_id)');
        $this->addSql('CREATE INDEX IDX_E79767628A8639B7 ON turno (oficina_id)');
        $this->addSql('CREATE TABLE persona (id INT NOT NULL, dni INT NOT NULL, apellido VARCHAR(50) NOT NULL, nombre VARCHAR(50) NOT NULL, email VARCHAR(100) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE oficina ADD CONSTRAINT FK_543A5AC67707C89 FOREIGN KEY (localidad_id) REFERENCES localidad (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE turno ADD CONSTRAINT FK_E7976762F5F88DB9 FOREIGN KEY (persona_id) REFERENCES persona (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE turno ADD CONSTRAINT FK_E79767628A8639B7 FOREIGN KEY (oficina_id) REFERENCES oficina (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE oficina DROP CONSTRAINT FK_543A5AC67707C89');
        $this->addSql('ALTER TABLE turno DROP CONSTRAINT FK_E79767628A8639B7');
        $this->addSql('ALTER TABLE turno DROP CONSTRAINT FK_E7976762F5F88DB9');
        $this->addSql('DROP SEQUENCE localidad_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE oficina_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE turno_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE persona_id_seq CASCADE');
        $this->addSql('DROP TABLE localidad');
        $this->addSql('DROP TABLE oficina');
        $this->addSql('DROP TABLE turno');
        $this->addSql('DROP TABLE persona');
    }
}
