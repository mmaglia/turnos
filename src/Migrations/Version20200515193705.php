<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200515193705 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE circunscripcion_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE config_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE circunscripcion (id INT NOT NULL, circunscripcion VARCHAR(50) NOT NULL, PRIMARY KEY(id))');
//        $this->addSql('CREATE TABLE config (id INT NOT NULL, clave VARCHAR(50) NOT NULL, valor VARCHAR(255) NOT NULL, html TEXT DEFAULT NULL, roles JSON DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE localidad ADD circunscripcion_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE localidad ADD CONSTRAINT FK_4F68E01011A99F19 FOREIGN KEY (circunscripcion_id) REFERENCES circunscripcion (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_4F68E01011A99F19 ON localidad (circunscripcion_id)');

        $this->addSql("INSERT INTO circunscripcion (id, circunscripcion) VALUES (nextval('circunscripcion_id_seq'), 'Santa Fe')");
        $this->addSql("INSERT INTO circunscripcion (id, circunscripcion) VALUES (nextval('circunscripcion_id_seq'), 'Rosario')");
        $this->addSql("INSERT INTO circunscripcion (id, circunscripcion) VALUES (nextval('circunscripcion_id_seq'), 'Venado Tuerto')");
        $this->addSql("INSERT INTO circunscripcion (id, circunscripcion) VALUES (nextval('circunscripcion_id_seq'), 'Reconquista')");
        $this->addSql("INSERT INTO circunscripcion (id, circunscripcion) VALUES (nextval('circunscripcion_id_seq'), 'Rafaela')");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE localidad DROP CONSTRAINT FK_4F68E01011A99F19');
        $this->addSql('DROP SEQUENCE circunscripcion_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE config_id_seq CASCADE');
        $this->addSql('DROP TABLE circunscripcion');
        $this->addSql('DROP TABLE config');
        $this->addSql('DROP INDEX IDX_4F68E01011A99F19');
        $this->addSql('ALTER TABLE localidad DROP circunscripcion_id');
    }
}
