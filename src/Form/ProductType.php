<?php

namespace App\Form;

use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Product Name',
                'attr' => [
                    'placeholder' => 'Product Name',
                    'class' => 'form-control form-control-sm',
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'The product name cannot be empty.',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'placeholder' => 'Description',
                    'class' => 'form-control form-control-sm',
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'The description cannot be empty.',
                    ]),
                ],
            ])
            ->add('price', NumberType::class, [
                'label' => 'Price',
                'html5' => true,
                'attr' => [
                    "min"  => 0.1,
                    'placeholder' => 'Price',
                    'class' => 'form-control form-control-sm',
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'The price cannot be empty.',
                    ]),
                    new Assert\Positive([
                        'message' => 'The price must be a positive number.',
                    ]),
                ],
            ])
            ->add('stockQuantity', NumberType::class, [
                'label' => 'Stock Quantity',
                'html5' => true,
                'attr' => [
                    'placeholder' => 'Stock Quantity',
                    'class' => 'form-control form-control-sm',
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'The stock quantity cannot be empty.',
                    ]),
                    new Assert\PositiveOrZero([
                        'message' => 'The stock quantity cannot be negative.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
