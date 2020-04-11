<?php

namespace App\Form;

use App\Entity\Persona;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;

class PersonaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('dni', NumberType::class, ['label' => 'DNI', 'required' => true, 'attr' => ['autofocus' => true, 'max' => '99999999']])
            ->add('apellido', null, ['label' => 'Apellido', 'required' => true, 'attr' => array('maxlength' => '50')])
            ->add('nombre', null, ['label' => 'Nombre', 'required' => true, 'attr' => array('maxlength' => '50')])
            ->add('email', EmailType::class, ['label' => 'Correo', 'required' => true, 'attr' => array('maxlength' => '80')])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Persona::class,
        ]);
    }
}
