<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class AddTurnosFromDateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('fechaReplica', DateType::class, [
            'widget' => 'single_text',
            'help' => '.',
            'html5' => false,
            'format' => 'dd/MM/yyyy',
            'label' => 'Día a Replicar',
            'attr' => ['class' => 'text-primary js-datepicker', 'autofocus' => false],
            'required' =>true,
            ])
            ->add('fechasDestino', DateType::class, [
                'widget' => 'single_text',
                'html5' => false,
                'format' => 'dd/MM/yyyy',
                'label' => 'Fechas Destino de la Réplica',
                'attr' => ['class' => 'text-primary js-datepicker'],
                'required' =>true,
                ])    
        ->add('save', SubmitType::class, [
            'label' => 'Confirmar',
            'attr' => ['class' => 'btn btn-primary float-right shadow']
            ])
        ;
    }
}
