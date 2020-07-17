<?php

namespace App\Form;

use App\Entity\Localidad;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use App\Entity\Circunscripcion;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class LocalidadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('localidad', TextType::class, ['required' => true, 'attr' => array('autofocus' => true, 'maxlength' => '80')])
            ->add('feriadosLocales', TextType::class, ['label' => 'Feriados Locales','help' => 'Ingrese lista en formato dd/mm separada por coma (,). Ej: 30/09, 07/10', 'attr' => array('maxlength' => '100')])
            ->add('circunscripcion', EntityType::class, ['required' => true, 'class' => Circunscripcion::class, 'placeholder' => 'Seleccione una Circunscripción', 'choice_label' => 'circunscripcion']);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Localidad::class,
        ]);
    }

    /**
     * Se agrega función para ordenar el combo de circunscripcion
     *
     * @param FormView $view
     * @param FormInterface $form
     * @param array $options
     * @return void
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        usort($view->children['circunscripcion']->vars['choices'], function (ChoiceView $a, ChoiceView $b) {
            return strcasecmp($a->label, $b->label);
        });
    }
}
