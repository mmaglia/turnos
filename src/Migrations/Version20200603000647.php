<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200603000647 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE organismo_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE organismo (id INT NOT NULL, localidad_id INT NOT NULL, codigo INT NOT NULL, organismo VARCHAR(255) NOT NULL, telefono VARCHAR(50) DEFAULT NULL, habilitado BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_3DDAAC2D67707C89 ON organismo (localidad_id)');
        $this->addSql('ALTER TABLE organismo ADD CONSTRAINT FK_3DDAAC2D67707C89 FOREIGN KEY (localidad_id) REFERENCES localidad (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE persona ADD organismo_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE persona ADD CONSTRAINT FK_51E5B69B3260D891 FOREIGN KEY (organismo_id) REFERENCES organismo (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_51E5B69B3260D891 ON persona (organismo_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE persona DROP CONSTRAINT FK_51E5B69B3260D891');
        $this->addSql('DROP SEQUENCE organismo_id_seq CASCADE');
        $this->addSql('DROP TABLE organismo');
        $this->addSql('DROP INDEX IDX_51E5B69B3260D891');
        $this->addSql('ALTER TABLE persona DROP organismo_id');
    }
}
