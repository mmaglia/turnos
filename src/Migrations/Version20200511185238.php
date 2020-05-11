<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200511185238 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE turnos_diarios_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE turnos_diarios (id INT NOT NULL, oficina_id INT DEFAULT NULL, fecha DATE NOT NULL, cantidad INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_73BDCE138A8639B7 ON turnos_diarios (oficina_id)');
        $this->addSql('ALTER TABLE turnos_diarios ADD CONSTRAINT FK_73BDCE138A8639B7 FOREIGN KEY (oficina_id) REFERENCES oficina (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql("INSERT INTO turnos_diarios (id, oficina_id, fecha, cantidad) VALUES (nextval('turnos_diarios_id_seq'), 1, '2020-05-07', 31)");
        $this->addSql("INSERT INTO turnos_diarios (id, oficina_id, fecha, cantidad) VALUES (nextval('turnos_diarios_id_seq'), 2, '2020-05-07', 135)");
        $this->addSql("INSERT INTO turnos_diarios (id, oficina_id, fecha, cantidad) VALUES (nextval('turnos_diarios_id_seq'), 1, '2020-05-08', 73)");
        $this->addSql("INSERT INTO turnos_diarios (id, oficina_id, fecha, cantidad) VALUES (nextval('turnos_diarios_id_seq'), 2, '2020-05-08', 209)");
        $this->addSql("INSERT INTO turnos_diarios (id, oficina_id, fecha, cantidad) VALUES (nextval('turnos_diarios_id_seq'), 1, '2020-05-09', 16)");
        $this->addSql("INSERT INTO turnos_diarios (id, oficina_id, fecha, cantidad) VALUES (nextval('turnos_diarios_id_seq'), 2, '2020-05-09', 54)");
        $this->addSql("INSERT INTO turnos_diarios (id, oficina_id, fecha, cantidad) VALUES (nextval('turnos_diarios_id_seq'), 1, '2020-05-10', 13)");
        $this->addSql("INSERT INTO turnos_diarios (id, oficina_id, fecha, cantidad) VALUES (nextval('turnos_diarios_id_seq'), 2, '2020-05-10', 36)");
        $this->addSql("INSERT INTO turnos_diarios (id, oficina_id, fecha, cantidad) VALUES (nextval('turnos_diarios_id_seq'), 1, '2020-05-11', 46)");
        $this->addSql("INSERT INTO turnos_diarios (id, oficina_id, fecha, cantidad) VALUES (nextval('turnos_diarios_id_seq'), 2, '2020-05-11', 234)");

    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE turnos_diarios_id_seq CASCADE');
        $this->addSql('DROP TABLE turnos_diarios');
    }
}
