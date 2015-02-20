<?php

namespace SS6\ShopBundle\Component\Router\FriendlyUrl;

use Doctrine\ORM\EntityManager;
use SS6\ShopBundle\Component\Router\FriendlyUrl\FriendlyUrl;

class FriendlyUrlRepository {

	/**
	 * @var \Doctrine\ORM\EntityManager
	 */
	private $em;

	/**
	 * @param \Doctrine\ORM\EntityManager $em
	 */
	public function __construct(EntityManager $em) {
		$this->em = $em;
	}

	/**
	 * @return \Doctrine\ORM\EntityRepository
	 */
	private function getFriendlyUrlRepository() {
		return $this->em->getRepository(FriendlyUrl::class);
	}

	/**
	 * @param int $domainId
	 * @param string $url
	 * @return \SS6\ShopBundle\Component\Router\FriendlyUrl\FriendlyUrl|null
	 */
	public function findByDomainIdAndUrl($domainId, $url) {
		return $this->getFriendlyUrlRepository()->findOneBy(
			[
				'domainId' => $domainId,
				'url' => $url,
			]
		);
	}

}
