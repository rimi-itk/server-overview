<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WebsiteType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
          ->add('domain', null, [
            'disabled' => true,
          ])
            ->add('server', null, [
            'disabled' => true,
          ])
            ->add('documentRoot', null, [
            'disabled' => true,
          ])
            ->add('type', null, [
            'disabled' => true,
          ])
            ->add('version', null, [
            'disabled' => true,
          ])
            ->add('data', null, [
            'disabled' => true,
          ])
            ->add('comments')
          ->add('save', SubmitType::class, [
              'label' => 'Save',
          ])
        ;
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
