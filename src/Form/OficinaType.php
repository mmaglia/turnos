<?php

namespace App\Form;

use App\Entity\Oficina;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;

use Symfony\Component\OptionsResolver\OptionsResolver;

class OficinaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('oficina')
            ->add('horaInicioAtencion')
            ->add('horaFinAtencion')
            ->add('frecuenciaAtencion')
            ->add('localidad')
            ->add('telefono', TextType::class, ['label' => 'Teléfono de Contacto', 'attr' => array('maxlength' => '50')])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Oficina::class,
        ]);
    }
}
