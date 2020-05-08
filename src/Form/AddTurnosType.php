<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\DateType;

class AddTurnosType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('fechaInicio', DateType::class, [
            'widget' => 'single_text',
            'html5' => false,
            'format' => 'dd/MM/yyyy',
            'label' => 'Seleccione Fecha de Inicio',
            'attr' => ['class' => 'text-primary js-datepicker'],
            'required' =>true,
            ])
        ->add('minutosDesplazamiento', NumberType::class,
        [   'label' => 'Minutos de Desplazamiento (0 si no quiere desplazar)',
            'html5' => true,
            'required' => true,
            'attr' => ['max' => '30', 'min' => '0', 'size' => 2]
        ])        
        ->add('cantidadDias', NumberType::class,
            [   'label' => 'Cantidad de Días',
                'html5' => true,
                'required' => true,
                'attr' => ['max' => '90', 'size' => 2, 'autofocus' => true]
            ])
        ;
    }
}
