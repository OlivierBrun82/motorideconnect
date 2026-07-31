<?php

namespace App\Form;

use App\Entity\User;
use App\Enum\DriverLevel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;


class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Adresse email',
                'attr' => ['autocomplete' => 'email'],
            ])
            ->add('pseudo', null, [
                'label' => 'Pseudo',
                'attr' => ['autocomplete' => 'username'],
            ])
            ->add('birthdate', DateType::class, [
                'label' => 'Date de naissance',
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'max' => (new \DateTimeImmutable('-16 years'))->format('Y-m-d'),
                ],
            ])
            ->add('phoneNumber', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'help' => 'Format : 0612345678 ou +33612345678',
                'attr' => ['placeholder' => 'votre numéro de téléphone',
                    'autocomplete' => 'tel',
                    'inputmode' => 'tel',    
                ],
            ])
            ->add('driverLvl', EnumType::class, [
                'label' => 'Niveau de pilotage',
                'class' => DriverLevel::class,
                'required' => false,
                'placeholder' => 'Quel est ton niveau de pilotage ?',
                'choice_label' => fn (DriverLevel $level) => $level->label(),
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'label' => 'J\'accepte les conditions générales',
                'mapped' => false,
                'constraints' => [
                    new IsTrue(
                        message: 'Vous devez accepter nos conditions générales.',
                    ),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Mot de passe',
                // non mappe : le mot de passe est lu puis encode dans le controleur
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank(
                        message: 'Veuillez entrer un mot de passe',
                    ),
                    new Length(
                        min: 8,
                        minMessage: 'Votre mot de passe doit faire au moins {{ limit }} characters',
                        // longueur maximale autorisee par Symfony
                        max: 4096,
                    ),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
