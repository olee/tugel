<?php

namespace Zeutzheim\PpintBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SearchType extends AbstractType {

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder->add('file', 'file', array(
			'label' => 'Upload file for scanning',
			'required' => false,
		));
		$builder->add('platform', 'entity', array(
			'label' => 'Platform',
			'class' => 'ZeutzheimPpintBundle:Platform',
			'required' => false,
		));
		$builder->add('tags', null, array(
			'label' => 'Tags',
			'required' => false,
		));
		$builder->add('languages', null, array(
			'label' => 'Languages',
			'required' => false,
		));
		$builder->add('namespaces', null, array(
			'label' => 'Namespaces',
			'required' => false,
		));
		$builder->add('classes', null, array(
			'label' => 'Classes',
			'required' => false,
		));
		$builder->add('submit', 'submit', array('label' => 'Suchen'));
	}
	
	public function setDefaultOptions(OptionsResolverInterface $resolver) {
		$resolver->setDefaults(array(
			'csrf_protection' => false,
		));
	}

	public function getName() {
		return 'search_form';
	}

}
