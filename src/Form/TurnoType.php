<?php

namespace App\Form;

use App\Entity\Turno;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use Symfony\Component\OptionsResolver\OptionsResolver;

class TurnoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('fechaHora', DateTimeType::class, [
                    'widget' => 'single_text',
                    'html5' => false,
                    'placeholder' => 'Seleccione una Fecha',
                    'attr' => ['class' => 'js-datepicker', 'date_format' => 'd/m/Y H:i'],
                    'mapped' => true
                ])
        ;

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function(FormEvent $event) {
                $data = $event->getData();
                $form = $event->getForm();

                if ($data->getPersona()) {
                    $form->add('motivo');
                    if ($_ENV['SISTEMA_ORALIDAD_CIVIL']) {
                        $form->add('notebook', null, ['help' => 'helperRequiereNotebook']);
                        $form->add('zoom', null, ['label'    => 'Requiere Zoom', 'help' => 'helperRequiereZoom']);
                    }
                    $form->add('estado', ChoiceType::class, [
                        'expanded' => true, // render check-boxes
                        'label'    => false,
                        'choices'  => [
                            'Sin Atender' => '1',
                            'Atendido' => '2',
                            'No asistió' => '3',
                            'Rechazado' => '4',
                        ]])
                    ;
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
