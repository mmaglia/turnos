<?php

namespace App\DataTables;

use App\Entity\Turno;
use Doctrine\ORM\QueryBuilder;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\BoolColumn;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use Omines\DataTablesBundle\Column\NumberColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\DataTable;
use Omines\DataTablesBundle\DataTableTypeInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * TableType para datatables de grilla de Turnos
 */
class TurnoTableType extends AbstractController implements DataTableTypeInterface
{
    protected $_translator;
    public function __construct(TranslatorInterface $translator)
    {
        $this->_translator = $translator;
    }

    /**
     * Configuro las columnas y sus funcionamiento de la grilla de turnos
     *
     * @param DataTable $dataTable
     * @param array $options [0] rango de fecha, [1] filtroEstado, [2] filtrooficina
     * @return void
     */
    public function configure(DataTable $dataTable, array $options)
    {

        $dataTable->add('oficina', TextColumn::class, ['label' => 'Oficina', 'field' => 't.oficina', 'searchable' => false]);
        $dataTable->add('fechaHora', DateTimeColumn::class, ['label' => 'Turno', 'format' => 'd-m-Y H:i', 'className' => 'text-center', 'searchable' => false]);
        $dataTable->add('persona', TextColumn::class, [
            'label' => $this->_translator->trans('Persona'), 'field' => 'p.apellido', 'searchable' => true, 'leftExpr' => "toUpper(p.apellido)",
            'render' => function ($value, $context) {
                if (is_null($context->getPersona())) {
                    return '';
                }
                // Armo Tooltip con Teléfono y Corrreo
                $email = $context->getPersona()->getEmail();
                $telefono = $context->getPersona()->getTelefono();
                if ($email && $telefono) {
                    return sprintf('<span title="%s">%s</span>', 'Correo: ' . $email . '. Tel. Contacto: ' . $telefono, $context->getPersona()->getPersona());
                }
                if ($email) {
                    return sprintf('<span title="%s">%s</span>', 'Correo: ' . $email, $context->getPersona()->getPersona());
                }
                if ($telefono) {
                    return sprintf('<span title="%s">%s</span>', 'Tel. Contacto: ' . $telefono, $context->getPersona()->getPersona());
                }
            },
            'rightExpr' => function ($value) {
                return '%' . strtoupper($value) . '%';
            }
        ]);
        $dataTable->add('dni', TextColumn::class, ['label' => $this->_translator->trans('DNI'), 'orderable' => true, 'field' => 'p.dni', 'searchable' => false, 'render' => function ($value, $context) {
            if ($_ENV['SISTEMA_ORALIDAD_CIVIL']) {
                return !is_null($context->getPersona())  ? $context->getPersona()->getOrganismo() . ' (' . $context->getPersona()->getDni() . ')' : '';
            }
            return !is_null($context->getPersona()) && !is_null($context->getPersona()->getDni()) ? $context->getPersona()->getDni() : '';
        }]);
        $dataTable->add('motivo', TextColumn::class, ['label' => $this->_translator->trans('Motivo'), 'searchable' => true, 'leftExpr' => "toUpper(t.motivo)", 'rightExpr' => function ($value) {
            return '%' . strtoupper($value) . '%';
        }]);
        if ($_ENV['SISTEMA_ORALIDAD_CIVIL']) {
            $dataTable->add('notebook', BoolColumn::class, ['label' => 'Notebook', 'searchable' => false, 'className' => 'text-center', 'trueValue' => '<i class="fas fa-check"></i>', 'falseValue' => '<i class="fa fa-times"></i>', 'nullValue' => '']);
            $dataTable->add('zoom', BoolColumn::class, ['label' => 'Zoom', 'searchable' => false, 'className' => 'text-center', 'trueValue' => '<i class="fas fa-check"></i>', 'falseValue' => '<i class="fa fa-times"></i>', 'nullValue' => '']);
        }
        $dataTable->add('estado', TextColumn::class, ['label' => 'Estado', 'searchable' => false, 'render' => function ($value, $context) {
            switch ($context->getEstado()) {
                case 1:
                    return $this->_translator->trans('Sin Atender');
                    break;
                case 2:
                    return $this->_translator->trans('Atendido');
                    break;
                case 3:
                    return $this->_translator->trans('No Asistió');
                    break;
                case 4:
                    return $this->_translator->trans('Rechazado');
                    break;
            }
        }]);
        if ($this->isGranted(('ROLE_EDITOR'))) {
            $dataTable->add('acciones', TextColumn::class, ['label' => 'Acciones', 'className' => 'text-center', 'render' => function ($value, $context) {
                $acciones = '<a href="' . $this->generateUrl('turno_show', ['id' => $context->getId()]) . '" title="Ver"><i class="fas fa-eye"></i></a>' .
                    '&nbsp;&nbsp;<a href="' . $this->generateUrl('turno_edit', ['id' => $context->getId()]) . '" title="Editar"><i class="fas fa-pen"></i></a>';
                if (!is_null($context->getPersona())) {
                    switch ($context->getEstado()) {
                        case 1:
                            $acciones .= '&nbsp;&nbsp;<a href="' . $this->generateUrl('turno_atendido', ['id' => $context->getId()]) . '" title="' . $this->_translator->trans('Marcar como atendido') . '"><i class="fas fa-user-check"></i></a>' .
                                '&nbsp;&nbsp;&nbsp;&nbsp;<a href="' . $this->generateUrl('turno_no_asistido', ['id' => $context->getId()]) . '" title="' . $this->_translator->trans('Marcar como Ausente') . '"><i class="fas fa-user-slash text-danger"></i></a>' .
                                '&nbsp<a href="' . $this->generateUrl('turno_rechazado', ['id' => $context->getId()]) . '" title="' . $this->_translator->trans('Rechazar') . '"><i class="fas fa-thumbs-down text-danger"></i></a>';
                            break;
                        case 2:
                            $acciones .= '&nbsp;&nbsp;<a href="' . $this->generateUrl('turno_atendido', ['id' => $context->getId()]) . '" title="' . $this->_translator->trans('Marcar como NO atendido') . '"><i class="fas fa-user-times"></i></a>';
                            break;
                    }
                }
                return $acciones;
            }]);
        }
        // Columnas duplicadas ocultas, para poder realizar busquedas individuales sobre campos puntuales
        $dataTable->add('dniSearch', NumberColumn::class, ['field' => 'p.dni', 'searchable' => true, 'visible' => false, 'operator' => 'LIKE', 'leftExpr' => "toChar(p.dni, '99999999')", 'rightExpr' => function ($value) {
            return '%' . $value . '%';
        }]);

        // Orden de la grilla
        $dataTable->addOrderBy('oficina', DataTable::SORT_ASCENDING);
        $dataTable->addOrderBy('fechaHora', DataTable::SORT_ASCENDING);

        $dataTable->createAdapter(ORMAdapter::class, [
            'entity' => Turno::class,
            'query' => function (QueryBuilder $builder) use ($options) {

                if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_AUDITORIA_GESTION')) {
                    // Busca los turnos en función a los estados de todas las oficinas
                    if ($options[2]) {
                        // Estado "todos"
                        if ($options[1] == 9) {
                            $builder->select('t')
                                ->from(Turno::class, 't')
                                ->leftjoin('t.persona', 'p')
                                ->where('t.oficina = :oficina')
                                ->andWhere('t.fechaHora BETWEEN :desde AND :hasta')
                                ->setParameter('oficina', $options[2])
                                ->setParameter('desde', $options[0]['desde'])
                                ->setParameter('hasta', $options[0]['hasta']);
                        } else {
                            // Suma filtro estado
                            $builder->select('t')
                                ->from(Turno::class, 't')
                                ->join('t.persona', 'p')
                                ->where('t.oficina = :oficina')
                                ->andWhere('t.fechaHora BETWEEN :desde AND :hasta')
                                ->andWhere($builder->expr()->isNotNull('t.persona'))
                                ->andWhere('t.estado = :estado')
                                ->setParameter('oficina', $options[2])
                                ->setParameter('desde', $options[0]['desde'])
                                ->setParameter('hasta', $options[0]['hasta'])
                                ->setParameter('estado', $options[1]);
                        }
                    } else {
                        // Estado "todos"
                        if ($options[1] == 9) {
                            if (!is_null($this->getUser()->getCircunscripcion())) {
                                $builder->select('t')
                                    ->from(Turno::class, 't')
                                    ->leftjoin('t.persona', 'p')
                                    ->join('t.oficina', 'o')
                                    ->join('o.localidad', 'l')
                                    ->where('l.circunscripcion = :circunscripcion')
                                    ->andWhere('t.fechaHora BETWEEN :desde AND :hasta')
                                    ->setParameter('circunscripcion', $this->getUser()->getCircunscripcion()->getId())
                                    ->setParameter('desde', $options[0]['desde'])
                                    ->setParameter('hasta', $options[0]['hasta']);
                            } else {
                                $builder->select('t')
                                    ->from(Turno::class, 't')
                                    ->leftjoin('t.persona', 'p')
                                    ->where('t.fechaHora BETWEEN :desde AND :hasta')
                                    ->setParameter('desde', $options[0]['desde'])
                                    ->setParameter('hasta', $options[0]['hasta']);
                            }
                        } else {
                            if (!is_null($this->getUser()->getCircunscripcion())) {
                                $builder->select('t')
                                    ->from(Turno::class, 't')
                                    ->join('t.persona', 'p')
                                    ->join('t.oficina', 'o')
                                    ->join('o.localidad', 'l')
                                    ->where('l.circunscripcion = :circunscripcion')
                                    ->andWhere('t.fechaHora BETWEEN :desde AND :hasta')
                                    ->andWhere('t.estado = :estado')
                                    ->setParameter('circunscripcion', $this->getUser()->getCircunscripcion()->getId())
                                    ->setParameter('desde', $options[0]['desde'])
                                    ->setParameter('hasta', $options[0]['hasta'])
                                    ->setParameter('estado', $options[1]);
                            } else {
                                $builder->select('t')
                                    ->from(Turno::class, 't')
                                    ->join('t.persona', 'p')
                                    ->where('t.fechaHora BETWEEN :desde AND :hasta')
                                    ->andWhere('t.estado = :estado')
                                    ->setParameter('desde', $options[0]['desde'])
                                    ->setParameter('hasta', $options[0]['hasta'])
                                    ->setParameter('estado', $options[1]);
                            }
                        }
                    }
                } else {
                    if ($this->isGranted('ROLE_USER')) {
                        // Busca los turnos en función a los estados de la oficina a la que pertenece el usuario
                        $oficinaUsuario = $this->getUser()->getOficina();
                        // Estado "todos"
                        if ($options[1] == 9) {
                            $builder->select('t')
                                ->from(Turno::class, 't')
                                ->leftjoin('t.persona', 'p')
                                ->where('t.oficina = :oficina')
                                ->andWhere('t.fechaHora BETWEEN :desde AND :hasta')
                                ->setParameter('oficina', $oficinaUsuario)
                                ->setParameter('desde', $options[0]['desde'])
                                ->setParameter('hasta', $options[0]['hasta']);
                        } else {
                            // Suma filtro estado
                            $builder->select('t')
                                ->from(Turno::class, 't')
                                ->join('t.persona', 'p')
                                ->where('t.oficina = :oficina')
                                ->andWhere('t.fechaHora BETWEEN :desde AND :hasta')
                                ->andWhere($builder->expr()->isNotNull('t.persona'))
                                ->andWhere('t.estado = :estado')
                                ->setParameter('oficina', $oficinaUsuario)
                                ->setParameter('desde', $options[0]['desde'])
                                ->setParameter('hasta', $options[0]['hasta'])
                                ->setParameter('estado', $options[1]);
                        }
                    }
                }
            }
        ]);
    }
}
