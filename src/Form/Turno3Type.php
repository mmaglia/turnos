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
            ->add('localidad', EntityType::class, [
                'class' => 'App\Entity\Localidad',
                'placeholder' => 'Seleccione una Localidad',
                'attr' => ['autofocus' => true],
                'required' => true,
                'mapped' => false
                ])
            ->add('oficina', EntityType::class, [
                'class' => 'App\Entity\Oficina',
                'label'    => 'Oficina',
                'required' => true,
                'placeholder' => 'Seleccione una Oficina',
                'mapped' => false,
                ])                
            ->add('motivo')
        ;

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
