<?php
/**
 * catalog_StockService
 * @package modules.catalog
 */
class catalog_StockService extends BaseService
{
	const LEVEL_AVAILABLE = 'A';
	const LEVEL_FEW_IN_STOCK = 'F';
	const LEVEL_UNAVAILABLE = 'U';

	const STOCK_ALERT_NOTIFICATION_CODENAME = 'modules_catalog/stockalert';

	/**
	 * Singleton
	 * @var catalog_StockService
	 */
	private static $instance = null;

	/**
	 * @return catalog_StockService
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return catalog_StockableDocument
	 */
	public function getStockableDocument($document)
	{
		if ($document instanceof catalog_StockableDocument)
		{
			return $document;
		}	
		return null;
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param Double $quantity
	 * @param catalog_persistentdocument_shop $shop
	 * @return Boolean
	 */
	public function isAvailable($document, $quantity = 1, $shop = null)
	{
		$stDoc = $this->getStockableDocument($document);
		if ($stDoc !== null)
		{
			$stock = $stDoc->getCurrentStockQuantity();
			return ($stock === null || ($stock - $quantity) >= 0);
		}
		return true;
	}
	
	/**
	 * @param catalog_StockableDocument $stDoc
	 * @param double $quantity
	 * @param order_CartInfo $cart
	 */
	protected function isValidCartQuantity($stDoc, $quantity, $cart)
	{
		if ($stDoc !== null)
		{
			$stock = $stDoc->getCurrentStockQuantity();
			return ($stock === null || ($stock - $quantity) >= 0);
		}
		return true;
	}
	
	
	/**
	 * @param array $productArray
	 * @param order_CartInfo $cart
	 * @return array
	 */
	public function validCartQuantities($productArray, $cart)
	{
		$result = array();
		foreach ($productArray as $productInfo) 
		{
			$stDoc = $this->getStockableDocument($productInfo[0]);
			if (!$this->isValidCartQuantity($stDoc, $productInfo[1], $cart))
			{
				$result[] = $productInfo;
			}
		}
		return $result;
	}	
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param catalog_persistentdocument_shop $shop
	 * @return string | null
	 */
	public function getAvailability($document, $shop = null)
	{
		$stDoc = $this->getStockableDocument($document);
		if ($stDoc !== null)
		{
			return catalog_AvailabilityStrategy::getStrategy()->getAvailability($stDoc->getCurrentStockLevel());
		}
		return catalog_AvailabilityStrategy::getStrategy()->getAvailability(null);
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param double $nb
	 * @return double new quantity
	 */
	public function increaseQuantity($document, $nb)
	{
		$result = null;
		$stDoc = $this->getStockableDocument($document);
		if ($stDoc !== null)
		{
			try 
			{
				$this->getTransactionManager()->beginTransaction();
				$result = $stDoc->addStockQuantity($nb);
				$this->getTransactionManager()->commit();
			}
			catch (Exception $e)
			{
				$this->getTransactionManager()->rollBack($e);
			}
		}
		return $result; 
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param integer $nb
	 * @param integer $orderId
	 * @return double new quantity
	 */
	public function decreaseQuantity($document, $nb, $orderId = null)
	{
		$result = null;
		$stDoc = $this->getStockableDocument($document);
		if ($stDoc !== null)
		{
			try 
			{
				$this->getTransactionManager()->beginTransaction();
				$result = $stDoc->addStockQuantity(-$nb);
				$this->getTransactionManager()->commit();
			}
			catch (Exception $e)
			{
				$this->getTransactionManager()->rollBack($e);
			}
		}
		return $result; 		
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 */
	public function handleStockAlert($document)
	{
		$stDoc = $this->getStockableDocument($document);
		if ($stDoc !== null)
		{
			if ($stDoc->mustSendStockAlert())
			{
				$recipients = ModuleService::getInstance()->getPreferenceValue('catalog', 'stockAlertNotificationUser');
				if ($recipients !== null && count($recipients) > 0)
				{
					$this->sendStockAlertNotification($recipients, $document);
				}
			}			
		}

	}
		
	/**
	 * @return catalog_StockableDocument
	 */
	public function getOutOfStockProducts()
	{
		$simpleproducts = catalog_SimpleproductService::getInstance()->createQuery()->add(Restrictions::le('stockQuantity', 0))->find();
		$productdeclinations = catalog_ProductdeclinationService::getInstance()->createQuery()->add(Restrictions::le('stockQuantity', 0))->find();
		return array_merge($simpleproducts, $productdeclinations);
	}
		
	// Private methods.
	
	/**
	 * @param Array<users_persistentdocument_backenduser> $recipients
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return boolean
	 */
	private function sendStockAlertNotification($users, $document)
	{
		$emailAddressArray = array();
		foreach ($users as $user)
		{
			$emailAddressArray[] = $user->getEmail();
		}
		$recipients = new mail_MessageRecipients();
		$recipients->setTo($emailAddressArray);

		$parameters = array(
			'label' => $document->getLabel(),
			'threshold' => $this->getAlertThreshold($document)
		);
		$ns = notification_NotificationService::getInstance();
		return $ns->send(
			$ns->getNotificationByCodeName(self::STOCK_ALERT_NOTIFICATION_CODENAME), 
			$recipients, 
			$parameters, 
			'catalog'
		);
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return Double
	 */
	private function getAlertThreshold($document)
	{
		$threshold = null;
		if (f_util_ClassUtils::methodExists($document, 'getStockAlertThreshold'))
		{
			$threshold = $document->getStockAlertThreshold();
		}
		
		if ($threshold === null)
		{
			return ModuleService::getInstance()->getPreferenceValue('catalog', 'stockAlertThreshold');
		}
		return $threshold;
	}
}
