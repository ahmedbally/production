<?php

namespace SS6\ShopBundle\Form\Admin\Order\Status;

use SS6\ShopBundle\Form\Admin\Order\Status\OrderStatusFormData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints;

class OrderStatusFormType extends AbstractType {

	/**
	 * @return string
	 */
	public function getName() {
		return 'order_status';
	}

	/**
	 * @param \Symfony\Component\Form\FormBuilderInterface $builder
	 * @param array $options
	 * @SuppressWarnings(PHPMD)
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('id', 'integer', array('read_only' => true, 'required' => false))
			->add('name', 'text', array(
				'constraints' => array(
					new Constraints\NotBlank(array('message' => 'Vyplňte prosím název stavu')),
					new Constraints\Length(array('max' => 100, 'maxMessage' => 'Název stavu nesmí být delší než {{ limit }} znaků')),
				)
			))
			->add('save', 'submit');
	}

	/**
	 * @param \Symfony\Component\OptionsResolver\OptionsResolverInterface $resolver
	 */
	public function setDefaultOptions(OptionsResolverInterface $resolver) {
		$resolver->setDefaults(array(
			'data_class' => OrderStatusFormData::class,
			'attr' => array('novalidate' => 'novalidate'),
		));
	}

}