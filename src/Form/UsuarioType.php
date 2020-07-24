<?php

namespace App\Form;

use App\Entity\Circunscripcion;
use App\Entity\Oficina;
use App\Entity\Usuario;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;

class UsuarioType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'username',
                null,
                [
                    'label' => 'Usuario',
                    'required' => true,
                    'attr' => array('autofocus' => null, 'maxlength' => '20')
                ]
            )
            ->add('oficina', EntityType::class, [
                'required' => false,
                'class' => Oficina::class,
                'placeholder' => 'Seleccione una Oficina',
                'choice_label' => function (Oficina $oficina) {
                    return $oficina->__toString();
                }
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'required' => true,
                'first_options' => ['label' => 'Contraseña'],
                'second_options' => ['label' => 'Confirme Contraseña']
            ])
            ->add('dni', NumberType::class, ['label' => 'DNI Usuario', 'required' => false, 'attr' => ['max' => '99999999']])
            ->add('apellido', null, ['label' => 'Apellido Usuario', 'attr' => array('maxlength' => '120')])
            ->add('nombre', null, ['label' => 'Nombre Usuario', 'attr' => array('maxlength' => '50')])
            ->add('email', EmailType::class, ['label' => 'Correo', 'required' => false, 'attr' => array('maxlength' => '50')])
            ->add('circunscripcion', EntityType::class, [
                'required' => false,
                'class' => Circunscripcion::class,
                'placeholder' => 'Seleccione una Circunscripción',
                'choice_label' => 'circunscripcion',
                'label'    => 'Circunscripción',
                'help' => 'Tenga en cuenta que seleccionar una circunscripción afectara toda la gestión de turnos'
            ])
            ->add('roles', ChoiceType::class, [
                'multiple' => true,
                'expanded' => true, // render check-boxes
                'label'    => 'Rol',
                'choices'  => [
                    'Consultor' => 'ROLE_CONSULTOR',
                    'Editor' => 'ROLE_EDITOR',
                    'Administrador de Portada' => 'ROLE_EDITOR',
                    'Auditoría de Gestión' => 'ROLE_AUDITORIA_GESTION',
                    'Administrador' => 'ROLE_ADMIN',
                    'Super Admin' => 'ROLE_SUPER_ADMIN'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Usuario::class,
        ]);
    }

    /**
     * Se agrega función para ordenar el combo de oficina
     *
     * @param FormView $view
     * @param FormInterface $form
     * @param array $options
     * @return void
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        usort($view->children['oficina']->vars['choices'], function (ChoiceView $a, ChoiceView $b) {
            return strcasecmp($a->label, $b->label);
        });
    }
}
