<?php

namespace App\Form;

use App\Entity\Localidad;
use App\Entity\Oficina;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OficinaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('oficina', TextType::class,  ['required' => true, 'label' => 'Oficina', 'attr' => array('autofocus' => true, 'maxlength' => '120')])
            ->add('horaInicioAtencion', null, ['required' => true, 'label' => 'Hora Inicio Atención'])
            ->add('horaFinAtencion', null, ['required' => true, 'label' => 'Hora Fin Atención'])
            ->add('frecuenciaAtencion', IntegerType::class, ['label' => 'Frecuencia de Atención', 'attr' => array('max' => '9999')])
            ->add('cantidadTurnosxturno', IntegerType::class, ['label' => 'Cantidad de Turnos por Turno', 'attr' => array('max' => '99')])
            ->add('localidad', EntityType::class, ['required' => true, 'class' => Localidad::class, 'placeholder' => 'Seleccione una Localidad', 'choice_label' => 'localidad'])
            ->add('telefono', TextType::class, ['label' => 'Teléfono de Contacto', 'required' => false, 'attr' => array('maxlength' => '200')])
            ->add('autoExtend', null, ['label' => 'Ampliar Agenda Automáticamente'])
            ->add('autoGestion', null, ['label' => 'Permitir que el usuario administre la Agenda (permite generar y borrar turnos en forma masiva)'])
            ->add('habilitada');
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Oficina::class,
        ]);
    }

    /**
     * Se agrega función para ordenar el combo de localidad
     *
     * @param FormView $view
     * @param FormInterface $form
     * @param array $options
     * @return void
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        usort($view->children['localidad']->vars['choices'], function (ChoiceView $a, ChoiceView $b) {
            return strcasecmp($a->label, $b->label);
        });
    }
}
