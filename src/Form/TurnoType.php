<?php

namespace App\Form;

use App\Entity\Turno;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;

use Symfony\Component\OptionsResolver\OptionsResolver;

class TurnoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('fechaHora', DateType::class, [
                    'widget' => 'single_text',
                    'placeholder' => 'Seleccione una Fecha',
                    'attr' => ['class' => 'js-datepicker'],
                    'mapped' => false
                ])
            ->add('motivo')
            ->add('persona')
            ->add('localidad', EntityType::class, [
                'class' => 'App\Entity\Localidad',
                'placeholder' => 'Seleccione una Localidad',
                'mapped' => false
                ])
        ;

        $builder->get('localidad')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                $form = $event->getForm();

                $form->getParent()->add('oficina', EntityType::class, [
                    'class' => 'App\Entity\Oficina',
                    'placeholder' => 'Seleccione una Oficina',
                    'choices' => $form->getData()->getOficinas()
                    ]);
            }
        ); 

        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            function (FormEvent $event) 
            {
                $form = $event->getForm();
                $data = $event->getData();
                $oficina = $data->getOficina();

                if ($oficina)
                {
                    $form->get('localidad')->setData($oficina->getLocalidad());
                    $form->add('oficina', EntityType::class, [
                        'class' => 'App\Entity\Oficina',
                        'placeholder' => 'Seleccione una Oficina',
                        'choices' => $oficina->getLocalidad()->getOficinas()
                    ]);
                } else {
/*                    $form->add('oficina', EntityType::class, [
                        'class' => 'App\Entity\Oficina',
                        'placeholder' => 'Seleccione una Oficina',
                        'choices' => []
                        ]);
*/                        
                }
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Turno::class,
        ]);
    }
}
