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
        $dataTable->add('id', TextColumn::class, ['label' => '#']);
        $dataTable->add('localidad', TextColumn::class, ['label' => 'Localidad']);
        $dataTable->add('circunscripcion', TextColumn::class, ['label' => 'Circunscripción', 'field' => 'l.circunscripcion']);
        /*$dataTable->add('acciones', '', ['label' => 'Acciones', 'render' => function($value, $context) {
            return sprintf('&nbsp;&nbsp;<a href="' . $this->generateUrl('localidad_show', ['id' => $context->getId()]) . '>%s</a>');
        }]);*/
        $dataTable->createAdapter(ORMAdapter::class, [
            'entity' => Localidad::class,
            'query' => function (QueryBuilder $builder) {
                $builder
                    ->select('l')
                    ->from(Localidad::class, 'l')
                    ->orderBy('l.localidad', 'ASC');
            }
        ]);
    }
}
