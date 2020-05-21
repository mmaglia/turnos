<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class AddTurnosType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('fechaInicio', DateType::class, [
            'widget' => 'single_text',
            'html5' => false,
            'format' => 'dd/MM/yyyy',
            'label' => 'Fecha de Inicio',
            'attr' => ['class' => 'text-primary js-datepicker', 'autofocus' => true],
            'required' =>true,
            ])
        ->add('feriados', DateType::class, [
            'widget' => 'single_text',
            'help' => 'Indique las fechas para las que no desea generar turnos (feriados y días no laborables)',
            'html5' => false,
            'format' => 'dd/MM/yyyy',
            'label' => 'Fechas exceptuadas',
            'attr' => ['class' => 'text-primary js-datepicker'],
            'required' =>false,
            ])
        ->add('minutosDesplazamiento', NumberType::class, [
            'label' => 'Minutos de Desplazamiento',
            'help' => 'Cero (0) si no quiere desplazar',
            'html5' => true,
            'required' => true,
            'attr' => ['max' => '30', 'min' => '0', 'size' => 2]
            ])        
        ->add('cantTurnosSuperpuestos', NumberType::class, [
            'label' => 'Cantidad de Turnos a generar por cada rango horario',
            'html5' => true,
            'required' => true,
            'attr' => ['max' => '30', 'min' => '1', 'size' => 2]
            ])            
        ->add('cantidadDias', NumberType::class, [
            'label' => 'Cantidad de Días',
            'help' => 'Indique por cuántos días corridos generar',
            'html5' => true,
            'required' => false,
            'attr' => ['max' => '90', 'size' => 2]
            ])
        ->add('fechaFin', DateType::class, [
            'widget' => 'single_text',
            'html5' => false,
            'format' => 'dd/MM/yyyy',
            'label' => 'Fecha Hasta',
            'help' => 'Indique hasta que día generar',
            'attr' => ['class' => 'text-primary js-datepicker', 'autofocus' => true],
            'required' =>false,
            ])
        ->add('soloUnTurno', CheckboxType::class, [
        'label' => 'Sólo un turno por rango horario.',
        'help' => 'Si el turno ya existe en el rango horario no se creará ninguno nuevo.',
        'required' => false,
        'attr' => ['class' => 'text-danger']
        ])    
        ->add('save', SubmitType::class, [
            'label' => 'Confirmar',
            'attr' => ['class' => 'btn btn-primary float-right']
            ])
        ;
    }
}
