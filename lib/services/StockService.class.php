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
	 * @param catalog_StockableDocument $document
	 * @param Double $quantity
	 * @return Boolean
	 */
	public function isAvailable($document, $quantity = 1)
	{
		$stock = $document->getStockQuantity();
		return ($stock === null || ($stock - $quantity) >= 0);
	}



	/**
	 * @param catalog_StockableDocument $document
	 * @param Integer $nb
	 */
	public function decreaseQuantity($document, $nb)
	{
		$document->addStockQuantity(-$nb);
		$document->save();
	}

	/**
	 * @param catalog_StockableDocument $document
	 * @param Integer $nb
	 */
	public function increaseQuantity($document, $nb)
	{
		$document->addStockQuantity($nb);
		$document->save();
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
	
	/**
	 * @param catalog_StockableDocument $document
	 */
	public function handleStockAlert($document)
	{
		if ($document->mustSendStockAlert())
		{
			$recipients = ModuleService::getInstance()->getPreferenceValue('catalog', 'stockAlertNotificationUser');
			if ($recipients !== null && count($recipients) > 0)
			{
				$this->sendStockAlertNotification($recipients, $document);
			}
			else
			{
				if (Framework::isDebugEnabled())
				{
					Framework::debug(__METHOD__ . ' no recipient.');
				}
			}
		}
	}
	
	// Private methods.
	
	/**
	 * @param catalog_StockableDocument $document
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
	
	/**
	 * @param Array<users_persistentdocument_backenduser> $recipients
	 * @param catalog_StockableDocument $document
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
	 * @deprecated use $document->getStockQuantity()
	 */
	public function getDisplayableQuantity($document)
	{
		return $document->getStockQuantity();
	}
	
	/**
	 * @deprecated 
	 */
	public function updateLevel($document)
	{
		$document->addStockQuantity(0);
		$document->save();
	}
		
	/**
	 * @deprecated 
	 */
	public function setLevelFromQuantity($document)
	{
		$document->addStockQuantity(0);
	}
}
