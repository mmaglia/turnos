<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200516095355 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE usuario ALTER apellido TYPE VARCHAR(120)');

        // Ajustes de Datos 
        // Santa Fe
        $this->addSql("UPDATE localidad SET circunscripcion_id = 1 WHERE id = 25");
        $this->addSql("UPDATE localidad SET circunscripcion_id = 1 WHERE id = 30");
        $this->addSql("UPDATE localidad SET circunscripcion_id = 1 WHERE id = 23");
        $this->addSql("UPDATE localidad SET circunscripcion_id = 1 WHERE id = 26");
        $this->addSql("UPDATE localidad SET circunscripcion_id = 1 WHERE id = 22");
        $this->addSql("UPDATE localidad SET circunscripcion_id = 1 WHERE id = 21");
        $this->addSql("UPDATE localidad SET circunscripcion_id = 1 WHERE id = 24");
        $this->addSql("UPDATE localidad SET circunscripcion_id = 1 WHERE id = 27");
        $this->addSql("UPDATE localidad SET circunscripcion_id = 1 WHERE id = 32");
        $this->addSql("UPDATE localidad SET circunscripcion_id = 1 WHERE id = 28");
        $this->addSql("UPDATE localidad SET circunscripcion_id = 1 WHERE id = 31");
        $this->addSql("UPDATE localidad SET circunscripcion_id = 1 WHERE id = 1");
        $this->addSql("UPDATE localidad SET circunscripcion_id = 1 WHERE id = 29");
        // Rosario
        $this->addSql("UPDATE localidad SET circunscripcion_id = 2 WHERE id = 10");
        $this->addSql("UPDATE localidad SET circunscripcion_id = 2 WHERE id = 11");
        $this->addSql("UPDATE localidad SET circunscripcion_id = 2 WHERE id = 12");
        $this->addSql("UPDATE localidad SET circunscripcion_id = 2 WHERE id = 2");
        $this->addSql("UPDATE localidad SET circunscripcion_id = 2 WHERE id = 9");
        // Venado Tuerto
        $this->addSql("UPDATE localidad SET circunscripcion_id = 3 WHERE id = 7");
        $this->addSql("UPDATE localidad SET circunscripcion_id = 3 WHERE id = 6");
        $this->addSql("UPDATE localidad SET circunscripcion_id = 3 WHERE id = 8");
        $this->addSql("UPDATE localidad SET circunscripcion_id = 3 WHERE id = 3");
        // Reconquista
        $this->addSql("UPDATE localidad SET circunscripcion_id = 4 WHERE id = 14");
        $this->addSql("UPDATE localidad SET circunscripcion_id = 4 WHERE id = 5");
        $this->addSql("UPDATE localidad SET circunscripcion_id = 4 WHERE id = 15");
        $this->addSql("UPDATE localidad SET circunscripcion_id = 4 WHERE id = 13");
        // Rafaela
        $this->addSql("UPDATE localidad SET circunscripcion_id = 5 WHERE id = 18");
        $this->addSql("UPDATE localidad SET circunscripcion_id = 5 WHERE id = 4");
        $this->addSql("UPDATE localidad SET circunscripcion_id = 5 WHERE id = 17");
        $this->addSql("UPDATE localidad SET circunscripcion_id = 5 WHERE id = 20");
        $this->addSql("UPDATE localidad SET circunscripcion_id = 5 WHERE id = 16");
        $this->addSql("UPDATE localidad SET circunscripcion_id = 5 WHERE id = 19");        

        // Habilita todas las Oficinas
        $this->addSql("UPDATE oficina SET habilitada = TRUE");
        
        // Ajusta apellido y nombre de los usuarios en función al organismo y la localidad
        $this->addSql("UPDATE usuario SET apellido = (SELECT oficina FROM oficina o WHERE o.id = usuario.oficina_id) WHERE apellido IS NULL");
        $this->addSql("UPDATE usuario SET nombre = '(' || (SELECT localidad FROM localidad l INNER JOIN oficina o ON o.localidad_id = l.id WHERE o.id = usuario.oficina_id) || ')' WHERE nombre IS NULL");

    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE usuario ALTER apellido TYPE VARCHAR(50)');
    }
}
