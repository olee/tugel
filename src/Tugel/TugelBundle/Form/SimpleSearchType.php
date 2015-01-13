<?php

namespace Tugel\TugelBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SimpleSearchType extends AbstractType {

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder->add('q', null, array(
			'label' => 'Search',
			'required' => true,
		));
		$builder->add('submit', 'submit', array('label' => 'Search'));
	}
	
	public function setDefaultOptions(OptionsResolverInterface $resolver) {
		$resolver->setDefaults(array(
			'csrf_protection' => false,
			'attr' => array(
				'id' => 'search',
			),
		));
	}

	public function getName() {
		return 'search_form';
	}

}
