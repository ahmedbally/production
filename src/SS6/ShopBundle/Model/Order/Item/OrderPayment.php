<?php

namespace SS6\ShopBundle\Model\Order\Item;

use Doctrine\ORM\Mapping as ORM;
use SS6\ShopBundle\Model\Order\Item\OrderItem;
use SS6\ShopBundle\Model\Order\Order;
use SS6\ShopBundle\Model\Payment\Payment;

/**
 * @ORM\Entity
 */
class OrderPayment extends OrderItem {

	/**
	 * @var \SS6\ShopBundle\Model\Payment\Payment
	 *
	 * @ORM\ManyToOne(targetEntity="SS6\ShopBundle\Model\Payment\Payment")
	 */
	private $payment;
	
	/**
	 * @param \SS6\ShopBundle\Model\Order\Order $order
	 * @param string $name
	 * @param string $price
	 * @param int $quantity
	 * @param \SS6\ShopBundle\Model\Payment\Payment $payment
	 */
	public function __construct(Order $order, $name, $price, $quantity, Payment $payment) {
		parent::__construct($order, $name, $price, $quantity);
		$this->payment = $payment;
	}

	/**
	 * @return \SS6\ShopBundle\Model\Payment\Payment
	 */
	public function getPayment() {
		return $this->payment;
	}

}