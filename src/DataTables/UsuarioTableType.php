<?php

namespace App\DataTables;

use App\Entity\Usuario;
use Doctrine\ORM\QueryBuilder;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\DataTable;
use Omines\DataTablesBundle\DataTableTypeInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * TableType para datatables de grilla de Usuarios
 */
class UsuarioTableType extends AbstractController implements DataTableTypeInterface
{
    /**
     * Configuro las columnas y sus funcionamiento de la grilla de usuarios
     *
     * @param DataTable $dataTable
     * @param array $options
     * @return void
     */
    public function configure(DataTable $dataTable, array $options)
    {
        $dataTable->add('username', TextColumn::class, ['label' => 'Usuario', 'searchable' => true, 'globalSearchable' => true]);
        $dataTable->add('rolesUsuario', TextColumn::class, ['label' => 'Roles', 'searchable' => false, 'render' => function ($value, $context) {
            return str_replace('ROLE_', '', implode(', ', $context->getRoles()));
        }]);
        $dataTable->addOrderBy('username', DataTable::SORT_ASCENDING);
        $dataTable->createAdapter(ORMAdapter::class, [
            'entity' => Usuario::class,
            'query' => function (QueryBuilder $builder) {
                $builder
                    ->select('u')
                    ->from(Usuario::class, 'u');
            }
        ]);
    }
}
