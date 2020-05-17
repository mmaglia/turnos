<?php

namespace App\Form;

use App\Entity\Localidad;
use App\Entity\Oficina;
use App\Repository\LocalidadRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OficinaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('oficina')
            ->add('horaInicioAtencion', null, ['required' => true, 'label' => 'Hora Inicio Atención'])
            ->add('horaFinAtencion', null, ['required' => true, 'label' => 'Hora Fin Atención'])
            ->add('frecuenciaAtencion', null, ['label' => 'Frecuencia de Atención'])
            ->add('localidad', EntityType::class, [
                'required' => false,
                'class' => Localidad::class,
                'query_builder' => function (LocalidadRepository $lr) {
                    $lr->findAll();
                },
                'placeholder' => 'Seleccione una Localidad',
                'choice_label' => 'localidad'])
            ->add('telefono', TextType::class, ['label' => 'Teléfono de Contacto', 'required' => false, 'attr' => array('maxlength' => '50')])
            ->add('habilitada');
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Oficina::class,
        ]);
    }
}
