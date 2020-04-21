<?php

namespace App\Form;

use App\Entity\Persona;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PersonaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('dni', IntegerType::class, ['label' => 'DNI', 'required' => true, 'attr' => array('autofocus' => true, 'min' => '1000000', 'max' => '99999999')])
            ->add('apellido', TextType::class, ['label' => 'Apellido', 'required' => true, 'attr' => array('maxlength' => '50')])
            ->add('nombre', TextType::class, ['label' => 'Nombre', 'required' => true, 'attr' => array('maxlength' => '50')])
            ->add('email', EmailType::class, ['label' => 'Correo', 'required' => true, 'attr' => array('maxlength' => '80')])
            ->add('telefono', TextType::class, ['label' => 'Teléfono de Contacto',  'required'   => false, 'attr' => array('maxlength' => '50')])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Persona::class,
        ]);
    }
}
