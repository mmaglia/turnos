<?php

namespace App\DataTables;

use App\Entity\TurnoRechazado;
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
 * TableType para datatables de grilla de Turnos Rechazados
 */
class TurnoRechazadoTableType extends AbstractController implements DataTableTypeInterface
{
    protected $_translator;
    public function __construct(TranslatorInterface $translator)
    {
        $this->_translator = $translator;
    }
    /**
     * Configuro las columnas y sus funcionamiento de la grilla de turnos rechazados
     *
     * @param DataTable $dataTable
     * @param array $options
     * @return void
     */
    public function configure(DataTable $dataTable, array $options)
    {
        if ($this->isGranted(('ROLE_ADMIN')) || $this->isGranted(('ROLE_AUDITORIA_GESTION'))) {
            $dataTable->add('oficina', TextColumn::class, ['label' => 'Oficina', 'field' => 't.oficina', 'searchable' => false]);
        }
        $dataTable->add('fechaHoraRechazo', DateTimeColumn::class, ['label' => 'Rechazado', 'format' => 'd/m/y H:i', 'className' => 'text-center', 'searchable' => true, 'operator' => 'LIKE', 'leftExpr' => "toChar(t.fechaHoraRechazo, 'DD/MM/YY HH24:MI')", 'rightExpr' => function ($value) {
            return '%' . $value . '%';
        }]);
        $dataTable->add('fechaHoraTurno', DateTimeColumn::class, ['label' => 'Turno', 'format' => 'd/m/y H:i', 'className' => 'text-center', 'searchable' => true, 'operator' => 'LIKE', 'leftExpr' => "toChar(t.fechaHoraTurno, 'DD/MM/YY HH24:MI')", 'rightExpr' => function ($value) {
            return '%' . $value . '%';
        }]);
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
            $dataTable->add('notebook', BoolColumn::class, ['label' => $this->_translator->trans('Notebook'), 'searchable' => false, 'className' => 'text-center', 'trueValue' => '<i class="fas fa-check"></i>', 'falseValue' => '<i class="fa fa-times"></i>', 'nullValue' => '']);
            $dataTable->add('zoom', BoolColumn::class, ['label' => $this->_translator->trans('Zoom'), 'searchable' => false, 'className' => 'text-center', 'trueValue' => '<i class="fas fa-check"></i>', 'falseValue' => '<i class="fa fa-times"></i>', 'nullValue' => '']);
        }
        $dataTable->add('motivoRechazo', TextColumn::class, ['label' => 'Motivo Rechazo', 'searchable' => true, 'leftExpr' => "toUpper(t.motivoRechazo)", 'raw' => true, 'rightExpr' => function ($value) {
            return '%' . strtoupper($value) . '%';
        }]);
        $dataTable->add('emailEnviado', BoolColumn::class, ['label' => 'Correo Enviado', 'searchable' => false, 'className' => 'text-center', 'trueValue' => '<i class="fas fa-check"></i>', 'falseValue' => '<i class="fa fa-times"></i>', 'nullValue' => '']);

        // Columnas duplicadas ocultas, para poder realizar busquedas individuales sobre campos puntuales
        $dataTable->add('dniSearch', NumberColumn::class, ['field' => 'p.dni', 'searchable' => true, 'visible' => false, 'operator' => 'LIKE', 'leftExpr' => "toChar(p.dni, '99999999')", 'rightExpr' => function ($value) {
            return '%' . $value . '%';
        }]);
        $dataTable->add('nombre', TextColumn::class, ['field' => 'p.nombre', 'searchable' => true, 'visible' => false, 'leftExpr' => "toUpper(p.nombre)", 'rightExpr' => function ($value) {
            return '%' . strtoupper($value) . '%';
        }]);

        // Orden de la grilla
        $dataTable->addOrderBy('fechaHoraTurno', DataTable::SORT_ASCENDING);
        $dataTable->createAdapter(ORMAdapter::class, [
            'entity' => TurnoRechazado::class,
            'query' => function (QueryBuilder $builder) use ($options) {
                if (count($options) > 0) {
                    $builder->select('t')->from(TurnoRechazado::class, 't')->where('t.oficina = ' . $options[0])->join('t.persona', 'p');
                } else {
                    $builder->select('t')->from(TurnoRechazado::class, 't')->join('t.persona', 'p');
                }
            }
        ]);
    }
}
