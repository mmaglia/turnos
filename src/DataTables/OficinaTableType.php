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
        // Se agrega la primera columna en blanco para poner el checkbox
        $dataTable->add('', TextColumn::class, ['label' => '']);
        $dataTable->add('id', TextColumn::class, ['label' => '#', 'searchable' => false]);
        $dataTable->add('oficina', TextColumn::class, ['label' => 'Oficina', 'searchable' => true, 'leftExpr' => "toUpper(o.oficina)", 'rightExpr' => function ($value) {
            return '%' . strtoupper($value) . '%';
        }]);
        $dataTable->add('localidad', TextColumn::class, ['label' => 'Localidad', 'searchable' => false,  'field' => 'o.localidad', 'orderField' => 'l.localidad']);
        $dataTable->add('horaInicioAtencion', DateTimeColumn::class, ['label' => 'Inicio', 'searchable' => false, 'orderable' => false, 'className' => 'text-center', 'format' => 'H:i']);
        $dataTable->add('horaFinAtencion', DateTimeColumn::class, ['label' => 'Fin', 'searchable' => false, 'orderable' => false, 'className' => 'text-center', 'format' => 'H:i']);
        $dataTable->add('frecuenciaAtencion', TextColumn::class, ['label' => 'Frecuencia', 'searchable' => false, 'orderable' => false, 'className' => 'text-center']);
        $dataTable->add('autoExtend', BoolColumn::class, ['label' => 'Extensión Automática', 'searchable' => false, 'className' => 'text-center', 'trueValue' => '<i class="fas fa-check"></i>', 'falseValue' => '<i class="fa fa-times"></i>', 'nullValue' => '']);
        $dataTable->add('autoGestion', BoolColumn::class, ['label' => 'Auto Gestión', 'searchable' => false, 'className' => 'text-center', 'trueValue' => '<i class="fas fa-check"></i>', 'falseValue' => '<i class="fa fa-times"></i>', 'nullValue' => '']);
        $dataTable->add('habilitada', BoolColumn::class, ['label' => 'Habilitada', 'searchable' => false, 'className' => 'text-center', 'trueValue' => '<i class="fas fa-check"></i>', 'falseValue' => '<i class="fa fa-times"></i>', 'nullValue' => 'unknown']);
        //$dataTable->add('ultimoTurno', DateTimeColumn::class, ['label' => 'Último Turno', 'format' => 'd-m-Y']);
        if ($this->isGranted(('ROLE_EDITOR'))) {
            $dataTable->add('acciones', TextColumn::class, ['label' => 'Acciones', 'className' => 'text-center', 'render' => function ($value, $context) {
                return '&nbsp;&nbsp;<a href="' . $this->generateUrl('oficina_show', ['id' => $context->getId()]) . '" title="Ver"><i class="fas fa-eye"></i></a>' .
                    '&nbsp;&nbsp;<a href="' . $this->generateUrl('oficina_edit', ['id' => $context->getId()]) . '" title="Editar"><i class="fas fa-pen"></i></a>' .
                    '&nbsp;&nbsp;<a href="' . $this->generateUrl('oficina_addTurnos', ['id' => $context->getId()]) . '" title="Crear Nuevos Turnos para ' . $context . '"><i class="far fa-calendar-plus"></i></a>' .
                    '&nbsp;&nbsp;<a href="' . $this->generateUrl('borraDiaAgendaTurnosbyOficina', ['id' => $context->getId()]) . '" title="Elimina un día de la Agenda de ' . $context . '"><i class="far fa-calendar-minus"></i></a>' .
                    '&nbsp;&nbsp;<a href="' . $this->generateUrl('oficina_addTurnosFromDate', ['id' => $context->getId()]) . '" title="Crear Nuevos Turnos a partir de un día como plantilla de la Agenda de ' . $context . '"><i class="far fa-calendar-check"></i></a>'
                    ;
            }]);
        }

        // Ordenes de la grilla
        $dataTable->addOrderBy('localidad', DataTable::SORT_ASCENDING);
        $dataTable->addOrderBy('horaInicioAtencion', DataTable::SORT_ASCENDING);
        $dataTable->addOrderBy('oficina', DataTable::SORT_ASCENDING);
        
        $dataTable->createAdapter(ORMAdapter::class, [
            'entity' => Oficina::class,
            'query' => function (QueryBuilder $builder) use ($options) {
                // De deja comentado el mecanismo encarado para determinar ultimoTurno (sin resolver todavía)
                /*$builder2 = clone $builder; 
                $subQuery = $builder2->select('MAX(t.fechaHora) as ultimoTurno')->from(Turno::class, 't')->where('t.oficina = o.id');
                $builder->select(array('partial o.{id, oficina, localidad, horaInicioAtencion, horaFinAtencion, frecuenciaAtencion, habilitada}'))->addSelect('(' . $subQuery->getDQL() . ')')->from(Oficina::class, 'o');*/
                if (count($options) > 0) {
                    $builder
                        ->select('o')
                        ->from(Oficina::class, 'o')
                        ->join('o.localidad', 'l')
                        ->where('o.id = ' . $options[0]);
                } else {
                    $builder
                        ->select('o')
                        ->from(Oficina::class, 'o')
                        ->join('o.localidad', 'l');
                }
            }
        ]);
    }
}
