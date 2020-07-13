<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200709083227 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE localidad ADD feriados_locales VARCHAR(100) DEFAULT NULL');
        $this->addSql("UPDATE localidad SET feriados_locales = '15/11'");
        $this->addSql("INSERT INTO public.config(id, clave, valor) VALUES (1, 'Feriados Nacionales', '15/06/2020, 09/07/2020, 10/07/2020, 17/08/2020, 12/10/2020, 16/11/2020, 23/11/2020, 07/12/2020, 08/12/2020, 25/12/2020, 31/12/2020')");
        $this->addSql("INSERT INTO public.config(id, clave, valor) VALUES (2, 'Umbral Agenda Llena', 80)");
        $this->addSql("INSERT INTO public.config(id, clave, valor) VALUES (3, 'Días mínimos futuros con turnos generados', 10)");
        $this->addSql("SELECT setval('config_id_seq', 2, true)");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE localidad DROP feriados_locales');
    }
}
