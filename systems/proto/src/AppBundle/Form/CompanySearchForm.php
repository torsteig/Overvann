<?php

namespace AppBundle\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class CompanySearchForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('search', SearchType::class, array(
                'label' => 'Bedriftssøk',
                'required' => false,
                'attr' => array('placeholder' => 'Søk på navn, lokasjon, ...')))
            ->add('save', SubmitType::class, array ('label' => 'Søk'));
    }
} 