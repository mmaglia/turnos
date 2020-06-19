<?php

namespace App\DataTables;

use App\Entity\Oficina;
use Doctrine\ORM\QueryBuilder;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\BoolColumn;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\DataTable;
use Omines\DataTablesBundle\DataTableTypeInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * TableType para datatables de grilla de Oficinas
 */
class OficinaTableType extends AbstractController implements DataTableTypeInterface
{
    /**
     * Configuro las columnas y sus funcionamiento de la grilla de oficina
     *
     * @param DataTable $dataTable
     * @param array $options
     * @return void
     */
    public function configure(DataTable $dataTable, array $options)
    {
        $dataTable->add('id', TextColumn::class, ['label' => '#', 'searchable' => false]);
        $dataTable->add('oficina', TextColumn::class, ['label' => 'Oficina', 'searchable' => true, 'globalSearchable' => true]);
        $dataTable->add('localidad', TextColumn::class, ['label' => 'Localidad', 'searchable' => false,  'field' => 'o.localidad']);
        $dataTable->add('horaInicioAtencion', DateTimeColumn::class, ['label' => 'Inicio', 'searchable' => false, 'format' => 'H:i']);
        $dataTable->add('horaFinAtencion', DateTimeColumn::class, ['label' => 'Fin', 'searchable' => false, 'format' => 'H:i']);
        $dataTable->add('frecuenciaAtencion', TextColumn::class, ['label' => 'Frecuencia', 'searchable' => false]);
        $dataTable->add('habilitada', BoolColumn::class, ['label' => 'Habilitada', 'searchable' => false, 'className' => 'text-center', 'trueValue' => '<i class="fas fa-check"></i>', 'falseValue' => '<i class="fa fa-times"></i>', 'nullValue' => 'unknown']);
        //$dataTable->add('ultimo_acceso', DateTimeColumn::class, ['label' => 'Último Turno', 'format' => 'd-m-Y']);
        if ($this->isGranted(('ROLE_EDITOR'))) {
            $dataTable->add('acciones', TextColumn::class, ['label' => 'Acciones', 'className' => 'text-center', 'render' => function ($value, $context) {
                return '&nbsp;&nbsp;<a href="' . $this->generateUrl('oficina_show', ['id' => $context->getId()]) . '" title="Ver"><i class="fas fa-eye"></i></a>' .
                    '&nbsp;&nbsp;<a href="' . $this->generateUrl('oficina_edit', ['id' => $context->getId()]) . '" title="Editar"><i class="fas fa-pen"></i></a>' .
                    '&nbsp;&nbsp;<a href="' . $this->generateUrl('oficina_addTurnos', ['id' => $context->getId()]) . '" title="Crear Nuevos Turnos para ' . $context . '"><i class="far fa-calendar-plus"></i></a>' .
                    '&nbsp;&nbsp;<a href="' . $this->generateUrl('borraDiaAgendaTurnosbyOficina', ['id' => $context->getId()]) . '" title="Elimina un día de la Agenda de ' . $context . '"><i class="far fa-calendar-minus"></i></a>';
            }]);
        }
        $dataTable->createAdapter(ORMAdapter::class, [
            'entity' => Oficina::class,
            'query' => function (QueryBuilder $builder) {
                /*$builder
                    ->select('o')
                    ->from(Oficina::class, 'o');*/
                $builder->getEntityManager()->createQuery('SELECT o.id as id, o.oficina as oficina, l.localidad as localidad, o.horaInicioAtencion as horaInicioAtencion, o.horaFinAtencion as horaFinAtencion, o.frecuenciaAtencion as frecuenciaAtencion, o.habilitada as habilitada,                        
                        FROM App\Entity\Oficina o 
                        left join o.localidad l
                        ORDER BY l.localidad, o.horaInicioAtencion, o.oficina');
            }
        ]);
        // (select max(t.fechaHora) from App\Entity\Turno t where t.oficina = o) as ultimoTurno
    }
}
