<?php

namespace App\DataTables;

use App\Entity\Config;
use Doctrine\ORM\QueryBuilder;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\DataTable;
use Omines\DataTablesBundle\DataTableTypeInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * TableType para datatables de grilla de Configuraciones
 */
class ConfigTableType extends AbstractController implements DataTableTypeInterface
{

    /**
     * Configuro las columnas y sus funcionamiento de la grilla de configuracion
     *
     * @param DataTable $dataTable
     * @param array $options
     * @return void
     */
    public function configure(DataTable $dataTable, array $options)
    {
        $dataTable->add('id', TextColumn::class, ['label' => '#', 'searchable' => false]);
        $dataTable->add('clave', TextColumn::class, ['label' => 'Clave', 'searchable' => true, 'leftExpr' => "toUpper(c.clave)", 'rightExpr' => function ($value) {
            return '%' . strtoupper($value) . '%';
        }]);
        $dataTable->add('valor', TextColumn::class, ['label' => 'Valor', 'className' => 'cell-breakWord', 'searchable' => true, 'leftExpr' => "toUpper(c.valor)", 'rightExpr' => function ($value) {
            return '%' . strtoupper($value) . '%';
        }]);
        if ($this->isGranted(('ROLE_EDITOR'))) {
            $dataTable->add('acciones', TextColumn::class, ['label' => 'Acciones', 'className' => 'text-center', 'render' => function ($value, $context) {
                return '&nbsp;&nbsp;<a href="' . $this->generateUrl('config_show', ['id' => $context->getId()]) . '" title="Ver"><i class="fas fa-eye"></i></a>' .
                    '&nbsp;&nbsp;<a href="' . $this->generateUrl('config_edit', ['id' => $context->getId()]) . '" title="Editar"><i class="fas fa-pen"></i></a>';
            }]);
        }

        // Orden de la grilla
        $dataTable->addOrderBy('id', DataTable::SORT_ASCENDING);

        // Creo el adaptador que recoger los datos
        $dataTable->createAdapter(ORMAdapter::class, [
            'entity' => Config::class,
            'query' => function (QueryBuilder $builder) {
                $builder
                    ->select('c')
                    ->from(Config::class, 'c');
            }
        ]);
    }
}
