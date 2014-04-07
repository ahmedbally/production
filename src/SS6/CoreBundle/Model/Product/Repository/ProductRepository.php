<?php

namespace SS6\CoreBundle\Model\Product\Repository;

use Doctrine\ORM\EntityManager;
use SS6\CoreBundle\Model\Product\Entity\Product;
use SS6\CoreBundle\Model\Product\Exception\ProductNotFoundException;

class ProductRepository {
	/** @var EntityManager */
	private $entityManager;

	/**
	 * @param EntityManager $entityManager
	 */
	public function __construct(EntityManager $entityManager) {
		$this->entityManager = $entityManager;
	}
	
	/**
	 * @param int $id
	 * @return Product|null
	 */
	public function findById($id) {
		return $this->entityManager->find('SS6CoreBundle:Product\Entity\Product', $id);
	}
	
	/**
	 * @param int $id
	 * @return Product|null
	 */
	public function findVisibleById($id) {
		$product = $this->findById($id);
		
		if ($product instanceof Product) {
			if (!$product->isVisible()) {
				$product = null;
			}
		}
		
		return $product;
	}
	
	/**
	 * @param int $id
	 * @return Product
	 */
	public function getById($id) {
		$product = $this->findById($id);
		
		if ($product === null) {
			throw new ProductNotFoundException('Product with ID ' . $id . ' does not exist.');
		}
		
		return $product;
	}
}
