<?php

namespace SS6\ShopBundle\DataFixtures\Demo;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use SS6\ShopBundle\Component\DataFixture\AbstractReferenceFixture;
use SS6\ShopBundle\DataFixtures\Base\AvailabilityDataFixture;
use SS6\ShopBundle\DataFixtures\Base\FlagDataFixture;
use SS6\ShopBundle\DataFixtures\Base\VatDataFixture;
use SS6\ShopBundle\DataFixtures\Demo\BrandDataFixture;
use SS6\ShopBundle\DataFixtures\Demo\CategoryDataFixture;
use SS6\ShopBundle\Model\Product\ProductEditData;

class ProductDataFixture extends AbstractReferenceFixture implements DependentFixtureInterface {

	const PRODUCT_PREFIX = 'product_';

	/**
	 * @param \Doctrine\Common\Persistence\ObjectManager $manager
	 */
	public function load(ObjectManager $manager) {
		$loaderService = $this->get('ss6.shop.data_fixtures.product_data_fixture_loader');
		/* @var $loaderService \SS6\ShopBundle\DataFixtures\Demo\ProductDataFixtureLoader */

		$vats = [
			'high' => $this->getReference(VatDataFixture::VAT_HIGH),
			'low' => $this->getReference(VatDataFixture::VAT_LOW),
			'zero' => $this->getReference(VatDataFixture::VAT_ZERO),
		];
		$availabilities = [
			'in-stock' => $this->getReference(AvailabilityDataFixture::IN_STOCK),
			'out-of-stock' => $this->getReference(AvailabilityDataFixture::OUT_OF_STOCK),
			'on-request' => $this->getReference(AvailabilityDataFixture::ON_REQUEST),
		];
		$categories = [
			'1' => $this->getReference(CategoryDataFixture::TV),
			'2' => $this->getReference(CategoryDataFixture::PHOTO),
			'3' => $this->getReference(CategoryDataFixture::PRINTERS),
			'4' => $this->getReference(CategoryDataFixture::PC),
			'5' => $this->getReference(CategoryDataFixture::PHONES),
			'6' => $this->getReference(CategoryDataFixture::COFFEE),
			'7' => $this->getReference(CategoryDataFixture::BOOKS),
			'8' => $this->getReference(CategoryDataFixture::TOYS),
		];

		$flags = [
			'action' => $this->getReference(FlagDataFixture::ACTION_PRODUCT),
			'new' => $this->getReference(FlagDataFixture::NEW_PRODUCT),
			'top' => $this->getReference(FlagDataFixture::TOP_PRODUCT),
		];

		$brands = [
			'apple' => $this->getReference(BrandDataFixture::APPLE),
			'canon' => $this->getReference(BrandDataFixture::CANON),
			'lg' => $this->getReference(BrandDataFixture::LG),
			'philips' => $this->getReference(BrandDataFixture::PHILIPS),
		];

		$loaderService->injectReferences($vats, $availabilities, $categories, $flags, $brands);
		$productsEditData = $loaderService->getProductsEditData();
		$productNo = 1;
		$productsByCatnum = [];
		foreach ($productsEditData as $productEditData) {
			$product = $this->createProduct(self::PRODUCT_PREFIX . $productNo, $productEditData);

			if ($product->getCatnum() !== null) {
				$productsByCatnum[$product->getCatnum()] = $product;
			}
			$productNo++;
		}

		$this->createVariants($productsByCatnum);

		$manager->flush();
	}

	/**
	 * @param string $referenceName
	 * @param \SS6\ShopBundle\Model\Product\ProductEditData $productEditData
	 * @return \SS6\ShopBundle\Model\Product\Product
	 */
	private function createProduct($referenceName, ProductEditData $productEditData) {
		$productEditFacade = $this->get('ss6.shop.product.product_edit_facade');
		/* @var $productEditFacade \SS6\ShopBundle\Model\Product\ProductEditFacade */

		$product = $productEditFacade->create($productEditData);

		$this->addReference($referenceName, $product);

		return $product;
	}

	/**
	 * @param \SS6\ShopBundle\Model\Product\Product[catnum] $productsByCatnum
	 */
	private function createVariants(array $productsByCatnum) {
		$loaderService = $this->get('ss6.shop.data_fixtures.product_data_fixture_loader');
		/* @var $loaderService \SS6\ShopBundle\DataFixtures\Demo\ProductDataFixtureLoader */

		$variantCatnumsByMainVariantCatnum = $loaderService->getVariantCatnumsIndexedByMainVariantCatnum();

		foreach ($variantCatnumsByMainVariantCatnum as $mainVariantCatnum => $variantsCatnums) {
			$mainVariant = $productsByCatnum[$mainVariantCatnum];
			/* @var $mainVariant \SS6\ShopBundle\Model\Product\Product */

			foreach ($variantsCatnums as $variantCatnum) {
				$mainVariant->addVariant($productsByCatnum[$variantCatnum]);
			}
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function getDependencies() {
		return [
			VatDataFixture::class,
			AvailabilityDataFixture::class,
			CategoryDataFixture::class,
			BrandDataFixture::class,
		];
	}

}
