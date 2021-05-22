<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210522110637 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP INDEX idx_oficina_oficina');
        $this->addSql('DROP INDEX idx_oficina_habilitada');
        $this->addSql('ALTER TABLE oficina ADD cantidad_turnosxturno SMALLINT DEFAULT 1');
        $this->addSql('DROP INDEX persona_dni_idx');
        $this->addSql('DROP INDEX idx_turno_fecha_hora');
        $this->addSql('DROP INDEX idx_turno_estado');
        $this->addSql('DROP INDEX idx_turno_rechazado_fecha_hora_rechazo');
        $this->addSql('DROP INDEX idx_turno_rechazado_fecha_hora_turno');
        $this->addSql('DROP INDEX idx_turnos_diarios_fecha');
        
        // Vuelvo a crear índices que se habían creado manualmente en la BD para que quede incorporado en el Gestor de Migraciones
        $this->addSql('CREATE INDEX idx_turno_estado ON turno (estado)');
        $this->addSql('CREATE INDEX idx_turno_fecha_hora ON turno (fecha_hora)');
        $this->addSql('CREATE INDEX idx_oficina_habilitada ON oficina (habilitada)');
        $this->addSql('CREATE INDEX idx_oficina_oficina ON oficina (oficina)');
        $this->addSql('CREATE INDEX persona_dni_idx ON persona (dni)');
        $this->addSql('CREATE INDEX idx_turnos_diarios_fecha ON turnos_diarios (fecha)');
        $this->addSql('CREATE INDEX idx_turno_rechazado_fecha_hora_turno ON turno_rechazado (fecha_hora_turno)');
        $this->addSql('CREATE INDEX idx_turno_rechazado_fecha_hora_rechazo ON turno_rechazado (fecha_hora_rechazo)');

        // Infiero cantidad de turnos para cada Oficina (algunas quedan sin inferencia por lo que se requiere control a través de la aplicación)
        $this->addSql("UPDATE oficina SET cantidad_turnosxturno = (select count(*) from turno where oficina_id = oficina.id and fecha_hora::date = '10-05-2021' and fecha_hora::time = oficina.hora_inicio_atencion)");
        $this->addSql("UPDATE oficina SET cantidad_turnosxturno = 1 WHERE (oficina ilike '%token%' or oficina ilike '%feria%')  and cantidad_turnosxturno = 0");
               
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE INDEX persona_dni_idx ON persona (dni)');
        $this->addSql('CREATE INDEX idx_turno_fecha_hora ON turno (fecha_hora)');
        $this->addSql('CREATE INDEX idx_turno_estado ON turno (estado)');
        $this->addSql('CREATE INDEX idx_turnos_diarios_fecha ON turnos_diarios (fecha)');
        $this->addSql('CREATE INDEX idx_turno_rechazado_fecha_hora_rechazo ON turno_rechazado (fecha_hora_rechazo)');
        $this->addSql('CREATE INDEX idx_turno_rechazado_fecha_hora_turno ON turno_rechazado (fecha_hora_turno)');
        $this->addSql('ALTER TABLE oficina DROP cantidad_turnosxturno');
        $this->addSql('CREATE INDEX idx_oficina_oficina ON oficina (oficina)');
        $this->addSql('CREATE INDEX idx_oficina_habilitada ON oficina (habilitada)');
    }
}
