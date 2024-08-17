<?php

namespace App\Form;

use App\Entity\Event;
use App\Entity\Association\Association;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre de l\'événement'
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description'
            ])
            ->add('startDate', DateTimeType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text'
            ])
            ->add('endDate', DateTimeType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text'
            ])
            ->add('location', TextType::class, [
                'label' => 'Lieu'
            ])
            ->add('maxCapacity', TextType::class, [
                'label' => 'Capacité maximale',
                'required' => false
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Prix',
                'required' => false,
                'currency' => 'EUR'
            ])
            ->add('association', EntityType::class, [
                'class' => Association::class,
                'choice_label' => 'name',
                'label' => 'Association'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}