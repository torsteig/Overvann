<?php

namespace AppBundle\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Gregwar\CaptchaBundle\Type\CaptchaType;

class ProjectType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder
			->add('name', TextType::class, array('attr' => array('placeholder' => 'name')))
			->add('field', TextType::class, array('attr' => array('placeholder' => 'field')))
			->add('startdate', DateTimeType::class)
			->add('enddate', DateTimeType::class)
//			->add('location', TextType::class, array('attr' => array('placeholder' => 'location')))
//			->add('technicalSolutions', TextType::class, array('attr' => array('placeholder' => 'technical solutions')))
			->add('description', TextType::class, array('attr' => array('placeholder' => 'description')))
			->add('save', SubmitType::class, array ('label' => 'Lag'))
			->add('captcha', CaptchaType::class, array(
			'label' => ' ',
			'width' => 200,
			'height' => 50,
			'length' => 5,
			'quality' =>200,
			'keep_value' => true,
			'distortion' => false,
			'background_color' => [255, 255, 255]));
	}

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'AppBundle\Entity\Project',
		));
	}

}