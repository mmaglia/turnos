<?php

namespace App\Form;

use App\Entity\Turno;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Turno3Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('circunscripcion', EntityType::class, [
                'class' => 'App\Entity\Circunscripcion',
                'placeholder' => 'Seleccione la Circunscripción',
                'attr' => ['autofocus' => true],
                'required' => true,
                'mapped' => false
                ])
            ->add('localidad', EntityType::class, [
                'class' => 'App\Entity\Localidad',
                'placeholder' => 'Seleccione una Localidad',
                'required' => true,
                'mapped' => false                
                ])               
            ->add('oficina', EntityType::class, [
                'class' => 'App\Entity\Oficina',
                'label'    => 'Oficina',
                'required' => true,
                'placeholder' => 'Seleccione una Oficina',
                'mapped' => false,
                'help'   => ($_ENV['SISTEMA_TURNOS_MPE'] ? '-' : '')
                ]);


        if ($_ENV['SISTEMA_TURNOS_WEB'] || $_ENV['SISTEMA_TURNOS_MPE'])
        {
            $builder->add('motivo');
        }

        if ($_ENV['SISTEMA_ORALIDAD_CIVIL'])
        {
            $builder
                ->add('motivo',null, ['help' => 'helpDatosAdicionales'])
                ->add('notebook', null, ['help' => 'helperRequiereNotebook'])
                ->add('zoom', null, ['label'    => 'Reunión Zoom', 'help' => 'helperRequiereZoom'])
            ;
        }
        

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) 
            {
                $data = $event->getData();
                $form = $event->getForm();

                //oficina is not mandatory
                if (!$data['oficina']) {
                    return;
                }
                $oficinaId = $data['oficina'];

                // Si se esta ejecutando MPE debo hacer un cambio de variables para identificar correctamente la oficina
                /*if($_ENV['SISTEMA_TURNOS_MPE']) {
                    if($oficinaId == -1) {
                        // Si se eligió causa nueva entonces seteo como oficina la OGD
                        if($data['tipo_causa'] == 1) {
                            $oficinaId = 2;
                        } else if($data['tipo_causa'] == 2) {
                            // Selecciono causa en trámite, entonces seteo como oficina la defensoria que venga
                            if(!$data['nro_defensoria']){
                                return;
                            }
                            $oficinaId = $data['nro_defensoria'];
                        }

                    }
                }*/
        
                $form->add('oficina', EntityType::class, array(
                    'class' => 'App\Entity\Oficina',
                    'label'    => 'Oficina',
                    'required' => true,
                    'placeholder' => 'Seleccione una Oficina',
                    'query_builder' => function(EntityRepository $er) use ($oficinaId){
                        return $er->createQueryBuilder('s')
                            ->where('s.id = ' . $oficinaId);
                    }));
            });


/*
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
//                    $form->add('oficina', EntityType::class, [
//                        'class' => 'App\Entity\Oficina',
//                        'placeholder' => 'Seleccione una Oficina',
//                       'choices' => []
//                        ]);
//                        
                }
            }
        );
*/        
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Turno::class,
        ]);
    }
}
