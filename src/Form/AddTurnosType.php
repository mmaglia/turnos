<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class AddTurnosType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('fechaInicio', DateType::class, [
            'widget' => 'single_text',
            'help' => '.',
            'html5' => false,
            'format' => 'dd/MM/yyyy',
            'label' => 'Fecha de Inicio',
            'attr' => ['class' => 'text-primary js-datepicker', 'autofocus' => true],
            'required' =>true,
            ])
        ->add('feriados', DateType::class, [
            'widget' => 'single_text',
            'help' => 'Indique las fechas adicionales para las que no desea generar turnos',
            'html5' => false,
            'format' => 'dd/MM/yyyy',
            'label' => 'Fechas exceptuadas adicionales a los feriados indicados',
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
        ->add('multiplicadorTurnos', PercentType::class, [
            'label' => 'Multiplicador de Turnos (100% no multiplica)',
            'help' => "Ej.: 50% reduce turnos a la mitad - 200% duplica turnos",
            'html5' => true,
            'required' => true,
            'attr' => ['max' => '500', 'min' => '0', 'size' => 3]
            ])            
        ->add('cantidadDias', NumberType::class, [
            'label' => 'Cantidad de Días',
            'help' => 'Indique cuántos días hábiles desea generar',
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
        ->add('save', SubmitType::class, [
            'label' => 'Confirmar',
            'attr' => ['class' => 'btn btn-primary float-right shadow']
            ])
        ;
    }
}
