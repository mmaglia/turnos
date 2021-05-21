<?php

namespace App\DataTables;

use App\Entity\Localidad;
use Doctrine\ORM\QueryBuilder;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\DataTable;
use Omines\DataTablesBundle\DataTableTypeInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * TableType para datatables de grilla de Localidades
 */
class LocalidadTableType extends AbstractController implements DataTableTypeInterface
{

    /**
     * Configuro las columnas y sus funcionamiento de la grilla de localidades
     *
     * @param DataTable $dataTable
     * @param array $options
     * @return void
     */
    public function configure(DataTable $dataTable, array $options)
    {
        $dataTable->add('id', TextColumn::class, ['label' => '#', 'searchable' => false]);
        $dataTable->add('localidad', TextColumn::class, ['label' => 'Localidad', 'searchable' => true, 'leftExpr' => "toUpper(l.localidad)", 'rightExpr' => function ($value) {
            return '%' . strtoupper($value) . '%';
        }]);
        $dataTable->add('feriadosLocales', TextColumn::class, ['label' => 'Feriados Locales', 'searchable' => false]);
        $dataTable->add('circunscripcion', TextColumn::class, ['label' => 'Circunscripción', 'searchable' => true,  'field' => 'l.circunscripcion', 'leftExpr' => "toUpper(c.circunscripcion)", 'rightExpr' => function ($value) {
            return '%' . strtoupper($value) . '%';
        }]);
        if ($this->isGranted(('ROLE_EDITOR'))) {
            $dataTable->add('acciones', TextColumn::class, ['label' => 'Acciones', 'className' => 'text-center', 'render' => function ($value, $context) {
                return '&nbsp;&nbsp;<a href="' . $this->generateUrl('localidad_show', ['id' => $context->getId()]) . '" title="Ver"><i class="fas fa-eye"></i></a>' .
                    '&nbsp;&nbsp;<a href="' . $this->generateUrl('localidad_edit', ['id' => $context->getId()]) . '" title="Editar"><i class="fas fa-pen"></i></a>' .
                    '&nbsp;&nbsp;<a href="' . $this->generateUrl('borraDiaAgendaTurnosbyLocalidad', ['id' => $context->getId()]) . '" title="Eliminar turnos sin Asignar en un rango de fecha de la Agenda de todas las oficinas en ' . $context . '"><i class="far fa-calendar-minus"></i></a>' .
                    '&nbsp;&nbsp;<a href="' . $this->generateUrl('habilitaDeshabilitaOficinasByLocalidad', ['id' => $context->getId(), 'accion' => 'true']) . '" title="Habilitar todas las Oficinas de ' . $context . '"><i class="far fa-calendar-check"></i></a>' .
                    '&nbsp;&nbsp;<a href="' . $this->generateUrl('habilitaDeshabilitaOficinasByLocalidad', ['id' => $context->getId(), 'accion' => 'false']) . '" title="deshabilitar todas las Oficinas de ' . $context . '"><i class="far fa-calendar-times"></i></a>';
            }]);
        }
        $dataTable->addOrderBy('localidad', DataTable::SORT_ASCENDING);
        $dataTable->createAdapter(ORMAdapter::class, [
            'entity' => Localidad::class,
            'query' => function (QueryBuilder $builder) {
                $builder
                    ->select('l')
                    ->from(Localidad::class, 'l')
                    ->join('l.circunscripcion', 'c');
            }
        ]);
    }
}
