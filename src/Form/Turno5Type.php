<?php

namespace App\Form;

use App\Entity\Turno;
use Symfony\Component\Form\AbstractType;

use Symfony\Component\OptionsResolver\OptionsResolver;


class Turno5Type extends AbstractType
{

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Turno::class,
        ]);
    }
}
