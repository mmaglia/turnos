<?php

namespace App\Form;

use App\Entity\Persona;
use App\Repository\OrganismoRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use App\Entity\Organismo;

class PersonaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($_ENV['SISTEMA_TURNOS_WEB'] || $_ENV['SISTEMA_TURNOS_MPE']) {
            $builder
                ->add('dni', IntegerType::class, ['label' => 'DNI', 'required' => true, 'attr' => array('autofocus' => true, 'min' => '1000000', 'max' => '99999999')])
                ->add('apellido', TextType::class, ['label' => 'Apellido', 'required' => true, 'attr' => array('maxlength' => '50')])
                ->add('nombre', TextType::class, ['label' => 'Nombre', 'required' => true, 'attr' => array('maxlength' => '50')])
                ->add('email', EmailType::class, ['label' => 'Correo', 'required' => ($_ENV['SISTEMA_TURNOS_WEB'] ? true : false), 'attr' => array('maxlength' => '80')])
                ->add('telefono', TextType::class, ['label' => 'Teléfono de Contacto',  'required'   => ($_ENV['SISTEMA_TURNOS_MPE'] ? true : false), 'attr' => array('maxlength' => '50')])
            ;
        }
        if ($_ENV['SISTEMA_ORALIDAD_CIVIL']) {
            $builder
                ->add('organismo', EntityType::class, [
                    'required'   => true,
                    'class' => Organismo::class,
                    'query_builder' => function (OrganismoRepository $er) {
                        return $er->createQueryBuilder('o')                                    
                                    ->innerJoin('o.localidad', 'l')
                                    ->where('o.habilitado = true')
                                    ->orderBy('l.localidad, o.organismo', 'ASC');
                    },
                ])    
                ->add('apellido', TextType::class, ['label' => 'Apellido', 'required' => true, 'attr' => array('maxlength' => '50')])
                ->add('nombre', TextType::class, ['label' => 'Nombre', 'required' => true, 'attr' => array('maxlength' => '50')])
                ->add('email', EmailType::class, ['label' => 'Correo', 'required' => true, 'attr' => array('maxlength' => '80')])
                ->add('telefono', TextType::class, ['label' => 'Teléfono de Contacto',  'required'   => false, 'attr' => array('maxlength' => '50')])
            ;
        }

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Persona::class,
        ]);
    }
}    

