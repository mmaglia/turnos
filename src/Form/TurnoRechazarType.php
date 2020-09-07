<?php

namespace App\Form;

use App\Entity\Turno;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

use Symfony\Component\OptionsResolver\OptionsResolver;

class TurnoRechazarType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('localidad', EntityType::class, [
                'class' => 'App\Entity\Localidad',
                'placeholder' => 'Seleccione una Localidad',
                'mapped' => false,
                'disabled' => true
                ])
        ;

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
                        'disabled' => true,
                        'choices' => $oficina->getLocalidad()->getOficinas()
                    ]);
                    $form->add('fechaHora', DateTimeType::class, [
                        'widget' => 'single_text',
                        'mapped' => true,
                        'disabled' => true
                    ]);
                    $form->add('motivo', null, ['label' => 'Motivo Indicado', 'disabled' => true]);

                    if ($_ENV['SISTEMA_ORALIDAD_CIVIL'])
                    {
                        $form->add('notebook', null, ['disabled' => true] );
                        $form->add('zoom', null, ['label' => 'Reunión Zoom', 'disabled' => true]);
                    }
            
                    $form->add('motivoRechazo', TextareaType::class, ['label' => 'Motivo del Rechazo', 'mapped' => false, 'attr' => array('autofocus' => true, 'maxlength' => '1000')]);
                    $form->add('enviarMail', CheckboxType::class, [
                        'label'    => 'Notificar por Correo',
                        'mapped'   => false,
                        'required' => false,
//                        'attr' => ['checked'   => 'checked']
                        ]);        
    
                }
            }
        );

        $builder->get('localidad')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                $form = $event->getForm();

                $form->getParent()->add('oficina', EntityType::class, [
                    'class' => 'App\Entity\Oficina',
                    'placeholder' => 'Seleccione una Oficina',
                    'choices' => $form->getData()->getOficinas(),
                    'disabled' => true
                    ]);
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
