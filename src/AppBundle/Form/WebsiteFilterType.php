<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type as Filters;
use Lexik\Bundle\FormFilterBundle\Filter\FilterOperands;

class WebsiteFilterType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
          ->add('domain', Filters\TextFilterType::class, [ 'condition_pattern' => FilterOperands::STRING_CONTAINS, 'label' => false ])
          ->add('server', Filters\TextFilterType::class, [ 'condition_pattern' => FilterOperands::STRING_CONTAINS, 'label' => false ])
          ->add('documentRoot', Filters\TextFilterType::class, [ 'condition_pattern' => FilterOperands::STRING_CONTAINS, 'label' => false ])
          ->add('type', Filters\TextFilterType::class, [ 'condition_pattern' => FilterOperands::STRING_CONTAINS, 'label' => false ])
          ->add('version', Filters\TextFilterType::class, [ 'condition_pattern' => FilterOperands::STRING_CONTAINS, 'label' => false ])
          ->add('data', Filters\TextFilterType::class, [ 'condition_pattern' => FilterOperands::STRING_CONTAINS, 'label' => false ])
          ->add('comments', Filters\TextFilterType::class, [ 'condition_pattern' => FilterOperands::STRING_CONTAINS, 'label' => false ])
          ->add('filter', SubmitType::class, [ 'label' => 'Filter' ]);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Website'
        ));
    }
}
