<?php

namespace SS6\ShopBundle\Model\FlashMessage;

use Symfony\Component\HttpFoundation\Session\Session;

class Bag {

	const MAIN_KEY = 'messages';

	const KEY_ERROR = 'error';
	const KEY_INFO = 'info';
	const KEY_SUCCESS = 'success';

	/**
	 * @var string
	 */
	protected $bagName;

	/**
	 * @var \Symfony\Component\HttpFoundation\Session\Session
	 */
	private $session;

	/**
	 * @param string $bagName
	 * @param \Symfony\Component\HttpFoundation\Session\Session $session
	 * @throws \SS6\ShopBundle\Model\Message\Exception\BagNameIsNotValidException
	 */
	public function __construct($bagName, Session $session) {
		if (!is_string($bagName) || empty($bagName)) {
			$message = 'Bag name for messages must be non-empty string.';
			throw new \SS6\ShopBundle\Model\Message\Exception\BagNameIsNotValidException($message);
		}

		$this->session = $session;
		$this->bagName = $bagName;
	}

	/**
	 * @param string|array $message
	 */
	public function addError($message, $isRaw = false) {
		$this->addMessage($message, $isRaw, self::KEY_ERROR);
	}

	/**
	 * @param string|array $message
	 */
	public function addInfo($message, $isRaw = false) {
		$this->addMessage($message, $isRaw, self::KEY_INFO);
	}

	/**
	 * @param string|array $message
	 */
	public function addSuccess($message, $isRaw = false) {
		$this->addMessage($message, $isRaw, self::KEY_SUCCESS);
	}

	/**
	 * @return array
	 */
	public function getErrorMessages() {
		return $this->getMessages(self::KEY_ERROR);
	}

	/**
	 * @return array
	 */
	public function getInfoMessages() {
		return $this->getMessages(self::KEY_INFO);
	}

	/**
	 * @return array
	 */
	public function getSuccessMessages() {
		return $this->getMessages(self::KEY_SUCCESS);
	}

	/**
	 * @return bool
	 */
	public function isEmpty() {
		$flashBag = $this->session->getFlashBag();

		return !$flashBag->has($this->getFullbagName(self::KEY_ERROR)) &&
			!$flashBag->has($this->getFullbagName(self::KEY_INFO)) &&
			!$flashBag->has($this->getFullbagName(self::KEY_SUCCESS));
	}

	/**
	 * @return string
	 */
	private function getFullbagName($key) {
		return self::MAIN_KEY . '__' . $this->bagName . '__' . $key;
	}

	/**
	 * @param string $key
	 * @return array
	 */
	private function getMessages($key) {
		$flashBag = $this->session->getFlashBag();
		$messages = $flashBag->get($this->getFullbagName($key));
		return array_unique($messages);
	}

	/**
	 * @param string|array $message
	 * @param boolean $isRaw
	 * @param string $key
	 */
	private function addMessage($message, $isRaw, $key) {
		if (is_array($message)) {
			foreach ($message as $item) {
				$this->addMessage($item, $isRaw, $key);
			}
		} else {
			if (!$isRaw) {
				$message = htmlspecialchars($message);
			}

			$this->session->getFlashBag()->add($this->getFullbagName($key), $message);
		}
	}

}