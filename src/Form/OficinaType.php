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
            ->add('horaInicioAtencion',  null, ['required' => true, 'label' => 'Hora Inicio Atención' ])
            ->add('horaFinAtencion', null, ['required' => true, 'label' => 'Hora Fin Atención' ])
            ->add('frecuenciaAtencion', null, ['label' => 'Frecuencia de Atención' ])
            ->add('localidad', null,  ['required' => false])
            ->add('telefono', TextType::class, ['label' => 'Teléfono de Contacto', 'required'   => false, 'attr' => array('maxlength' => '50')])
            ->add('habilitada')

        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Oficina::class,
        ]);
    }
}
