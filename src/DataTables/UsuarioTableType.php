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
        $dataTable->add('id', TextColumn::class, ['label' => '#', 'searchable' => false]);
        $dataTable->add('username', TextColumn::class, ['label' => 'Usuario', 'searchable' => true, 'leftExpr' => "toUpper(u.username)", 'rightExpr' => function ($value) {
            return '%' . strtoupper($value) . '%';
        }]);
        $dataTable->add('rolesUsuario', TextColumn::class, ['label' => 'Roles', 'searchable' => false, 'render' => function ($value, $context) {
            return str_replace('ROLE_', '', implode(', ', $context->getRoles()));
        }]);
        $dataTable->add('apenom', TextColumn::class, [
            'label' => 'Apellido y Nombres', 'orderable' => true, 'searchable' => true, 'leftExpr' => "toUpper(u.apellido)", 'field' => 'u.apellido',
            'render' => function ($value, $context) {
                return $context->getApeNom();
            },
            'rightExpr' => function ($value) {
                return '%' . strtoupper($value) . '%';
            }
        ]);
        $dataTable->add('oficina', TextColumn::class, ['label' => 'Oficina', 'searchable' => false, 'field' => 'o.oficina', 'leftExpr' => "toUpper(o.oficina)", 'rightExpr' => function ($value) {
            return '%' . strtoupper($value) . '%';
        }]);
        $dataTable->add('fecha_alta', DateTimeColumn::class, ['label' => 'Alta', 'format' => 'd-m-Y', 'className' => 'text-center', 'searchable' => true, 'operator' => 'LIKE', 'leftExpr' => "toChar(u.fecha_alta, 'DD-MM-YYYY HH24:MI:SS')", 'rightExpr' => function ($value) {
            return '%' . $value . '%';
        }]);
        $dataTable->add('fecha_baja', DateTimeColumn::class, ['label' => 'Baja', 'format' => 'd-m-Y', 'className' => 'text-center', 'searchable' => true, 'operator' => 'LIKE', 'leftExpr' => "toChar(u.fecha_baja, 'DD-MM-YYYY HH24:MI:SS')", 'rightExpr' => function ($value) {
            return '%' . $value . '%';
        }]);
        $dataTable->add('ultimo_acceso', DateTimeColumn::class, ['label' => 'Último Acceso', 'format' => 'd-m-Y', 'className' => 'text-center', 'searchable' => true, 'operator' => 'LIKE', 'leftExpr' => "toChar(u.ultimo_acceso, 'DD-MM-YYYY HH24:MI:SS')", 'rightExpr' => function ($value) {
            return '%' . $value . '%';
        }]);
        $dataTable->add('cantidad_accesos', TextColumn::class, ['label' => 'Accesos', 'className' => 'text-center', 'searchable' => false]);
        if ($this->isGranted(('ROLE_EDITOR'))) {
            $dataTable->add('acciones', TextColumn::class, ['label' => 'Acciones', 'className' => 'text-center', 'render' => function ($value, $context) {
                return '&nbsp;&nbsp;<a href="' . $this->generateUrl('usuario_show', ['id' => $context->getId()]) . '" title="Ver"><i class="fas fa-eye"></i></a>' .
                    '&nbsp;&nbsp;<a href="' . $this->generateUrl('usuario_edit', ['id' => $context->getId()]) . '" title="Editar"><i class="fas fa-pen"></i></a>';
            }]);
        }

        // Columnas duplicadas ocultas, para poder realizar busquedas individuales sobre campos puntuales
        $dataTable->add('nombre', TextColumn::class, ['searchable' => true, 'visible' => false, 'leftExpr' => "toUpper(u.nombre)", 'rightExpr' => function ($value) {
            return '%' . strtoupper($value) . '%';
        }]);

        // Orden de la grilla
        $dataTable->addOrderBy('username', DataTable::SORT_ASCENDING);

        $dataTable->createAdapter(ORMAdapter::class, [
            'entity' => Usuario::class,
            'query' => function (QueryBuilder $builder) {
                $builder
                    ->select('u')
                    ->from(Usuario::class, 'u')
                    ->leftjoin('u.oficina', 'o');
            }
        ]);
    }
}
