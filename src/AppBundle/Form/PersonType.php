<?php
namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Gregwar\CaptchaBundle\Type\CaptchaType;

class PersonType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder
			->add('email', EmailType::class)
			->add('lastName', TextType::class)
			->add('firstName', TextType::class)
			->add('save', SubmitType::class, array('label' => 'Lag person',))
			->add('captcha', CaptchaType::class, array(
			'label' => ' ',
			'width' => 200,
			'height' => 50,
			'length' => 5,
			'quality' =>200,
			'keep_value' => true,
			'distortion' => false,
			'background_color' => [255, 255, 255],
		));
	}

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'AppBundle\Entity\Person',
		));
	}
}