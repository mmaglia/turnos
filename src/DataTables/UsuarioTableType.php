<?php

namespace App\DataTables;

use App\Entity\Usuario;
use Doctrine\ORM\QueryBuilder;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\DateTimeColumn;
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
        $dataTable->add('apenom', TextColumn::class, ['label' => 'Apellido y Nombres', 'searchable' => true, 'globalSearchable' => true, 'render' => function ($value, $context) {
            $apellido = $context->getApellido();
            $nombres = $context->getNombre();
            if ($apellido && $nombres)
                return $apellido . ', ' . $nombres;
            else if ($apellido && !$nombres)
                return $apellido;
            else if (!$apellido && $nombres)
                return $nombres;
            return '';
        }]);
        $dataTable->add('oficina', TextColumn::class, ['label' => 'Oficina', 'searchable' => false,  'field' => 'u.oficina']);
        $dataTable->add('ultimo_acceso', DateTimeColumn::class, ['label' => 'Acceso', 'format' => 'd-m-Y']);
        $dataTable->add('cantidad_accesos', TextColumn::class, ['label' => 'Accesos', 'className' => 'text-center']);
        if ($this->isGranted(('ROLE_EDITOR'))) {
            $dataTable->add('acciones', TextColumn::class, ['label' => 'Acciones', 'className' => 'text-center', 'render' => function ($value, $context) {
                return '&nbsp;&nbsp;<a href="' . $this->generateUrl('usuario_show', ['id' => $context->getId()]) . '" title="Ver"><i class="fas fa-eye"></i></a>' .
                    '&nbsp;&nbsp;<a href="' . $this->generateUrl('usuario_edit', ['id' => $context->getId()]) . '" title="Editar"><i class="fas fa-pen"></i></a>';
            }]);
        }
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
