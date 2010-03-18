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
	 */
	public function updateLevel($document)
	{
		$this->setLevelFromQuantity($document);
		$document->save();
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
	 */
	public function setLevelFromQuantity($document)
	{
		if ($this->isAvailable($document))
		{
			$stockLevel = $document->getStockLevel();
			if ($stockLevel == self::LEVEL_UNAVAILABLE || $stockLevel === null)
			{
				$document->setStockLevel(self::LEVEL_AVAILABLE);
			}
		}
		else
		{
			$document->setStockLevel(self::LEVEL_UNAVAILABLE);
		}
	}

	/**
	 * @param catalog_StockableDocument $document
	 * @param Integer $nb
	 */
	public function decreaseQuantity($document, $nb)
	{
		$quantity = $document->getStockQuantity();
		if ($quantity !== null)
		{
			$document->setStockQuantity($quantity - $nb);
			$document->save();
		}
	}

	/**
	 * @param catalog_StockableDocument $document
	 * @param Integer $nb
	 */
	public function increaseQuantity($document, $nb)
	{
		$quantity = $document->getStockQuantity();
		if ($quantity !== null)
		{
			$document->setStockQuantity($quantity + $nb);
			$document->save();
		}
	}
	
	/**
	 * Gets substract the out of stock threshold to the quantity. 
	 * @param catalog_StockableDocument $document
	 * @return Double
	 */
	public function getDisplayableQuantity($document)
	{
		$quantity = $document->getStockQuantity();
		return ($quantity === null) ? null : $quantity;
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
		$theshold = $this->getAlertThreshold($document);
		if ($document->getStockQuantityOldValue() > $theshold && $document->getStockQuantity() <= $theshold)
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
		if ($document instanceof catalog_StockableDocument && $document->getStockAlertThreshold() !== null)
		{
			return $document->getStockAlertThreshold();
		}
		return ModuleService::getInstance()->getPreferenceValue('catalog', 'stockAlertThreshold');
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
}
