<?php

namespace App\Form;

use App\Entity\Config;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use FOS\CKEditorBundle\Form\Type\CKEditorType;


class ConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('clave', null, [
                'attr' => ['autofocus' => true, 'maxlength' => '150']
            ])
            ->add('valor')
            ->add('html', CKEditorType::class, [
                'config' => [
                    'uiColor' => '#ffffff'
                ]
            ])
            ->add('roles', ChoiceType::class, [
                'multiple' => true,
                'expanded' => true, // render check-boxes
                'label'    => 'Rol',
                'choices'  => [
                    'Consultor' => 'ROLE_CONSULTOR',
                    'Editor' => 'ROLE_EDITOR',
                    'Administrador de Portada' => 'ROLE_COVER_MANAGER',
                    'Auditoría de Gestión' => 'ROLE_AUDITORIA_GESTION',
                    'Administrador' => 'ROLE_ADMIN',
                    'Super Admin' => 'ROLE_SUPER_ADMIN'
                ]])
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Config::class,
        ]);
    }
}
