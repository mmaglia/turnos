<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class AddTurnosType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('cantidadDias', NumberType::class,
            [   'label' => 'Cantidad de Días',
                'html5' => true,
                'required' => true,
                'attr' => ['max' => '30', 'size' => 2, 'autofocus' => true]
            ])
        ;
    }
}
