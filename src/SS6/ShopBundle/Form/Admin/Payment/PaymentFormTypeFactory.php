<?php

namespace SS6\ShopBundle\Form\Admin\Payment;

use SS6\ShopBundle\Model\FileUpload\FileUpload;
use SS6\ShopBundle\Model\Pricing\VatRepository;
use SS6\ShopBundle\Model\Transport\TransportRepository;

class PaymentFormTypeFactory {

	/**
	 * @var \SS6\ShopBundle\Model\Transport\TransportRepository
	 */
	private $transportRepository;

	/**
	 * @var \SS6\ShopBundle\Model\FileUpload\FileUpload
	 */
	private $fileUpload;

	/**
	 * @var \SS6\ShopBundle\Model\Pricing\VatRepository
	 */
	private $vatRepository;

	/**
	 * @param \SS6\ShopBundle\Model\Transport\TransportRepository $transportRepository
	 * @param \SS6\ShopBundle\Model\FileUpload\FileUpload $fileUpload
	 * @param \SS6\ShopBundle\Model\Pricing\VatRepository $vatRepository
	 */
	public function __construct(
		TransportRepository $transportRepository,
		FileUpload $fileUpload,
		VatRepository $vatRepository
	) {
		$this->transportRepository = $transportRepository;
		$this->fileUpload = $fileUpload;
		$this->vatRepository = $vatRepository;
	}

	/**
	 * @return \SS6\ShopBundle\Form\Admin\Payment\PaymentFormType
	 */
	public function create() {
		$allTransports = $this->transportRepository->findAll();
		$vats = $this->vatRepository->findAll();

		return new PaymentFormType($allTransports, $this->fileUpload, $vats);
	}

}