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
	 * @param catalog_persistentdocument_product $document
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
	 * @param catalog_persistentdocument_product $document
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
	 *
	 * @param catalog_persistentdocument_product $product
	 * @param integer $quantity
	 * @param array $globalProductsArray
	 */
	protected function buildProductList($product, $quantity, &$globalProductsArray)
	{
		$productId = $product->getId();
		if ($product instanceof catalog_BundleProduct)
		{
			foreach ($product->getBundledProducts() as $bundledProduct)
			{
				$product = $bundledProduct->getProduct();
				$productId = $product->getId();
				$productQty = $bundledProduct->getQuantity() * $quantity;
				if (! isset($globalProductsArray[$productId]))
				{
					$globalProductsArray[$productId] = array($product, $productQty);
				}
				else
				{
					$globalProductsArray[$productId][1] += $productQty;
				}
			}
		}
		else
		{
			$productQty = $quantity;
			if (! isset($globalProductsArray[$productId]))
			{
				$globalProductsArray[$productId] = array($product, $productQty);
			}
			else
			{
				$globalProductsArray[$productId][1] += $productQty;
			}
		}
	}
	
	/**
	 * @param order_CartInfo $cart
	 * @return array
	 */
	public function validateCart($cart)
	{
		if ($cart->getShop()->getAllowOrderOutOfStock())
		{
			return array();
		}
		
		$cartLines = $cart->getCartLineArray();
		$productArray = array();	
		foreach ($cartLines as $index => $cartLine)
		{
			$this->buildProductList($cartLine->getProduct(), $cartLine->getQuantity(), $productArray);
		}	
		
		return $this->validateCartQuantities($productArray, $cart);
	}
	
	/**
	 * @param array $productArray
	 * @param order_CartInfo $cart
	 * @return array
	 */
	protected function validateCartQuantities($productArray, $cart)
	{
		return $this->validCartQuantities($productArray, $cart);
	}
	
	/**
	 * @deprecated use validCart
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
			if ($stDoc !== null)
			{
				$stock = $stDoc->getCurrentStockQuantity();
				if ($stock !== null && ($stock - $productInfo[1]) < 0)
				{
					$result[] = $productInfo;
				}
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
	 * @return catalog_StockableDocument
	 */
	public function getOutOfStockProducts()
	{
		return catalog_ProductService::getInstance()->createQuery()->add(Restrictions::le('stockQuantity', 0))->find();
	}

	/**
	 * @return integer
	 */
	public function getOutOfStockProductsCount()
	{
		return f_util_ArrayUtils::firstElement(catalog_ProductService::getInstance()->createQuery()->add(Restrictions::le('stockQuantity', 0))->setProjection(Projections::rowCount('count'))->findColumn('count'));
	}

	/**
	 * @param order_CartInfo $cart
	 * @param order_persistentdocument_order $order
	 */
	public function orderInitializedFromCart($cart, $order)
	{

	}

	/**
	 * @param order_persistentdocument_order $order
	 * @param string $oldStatus
	 */
	public function orderStatusChanged($order, $oldStatus)
	{
		if (Framework::isInfoEnabled())
		{
			Framework::info(__METHOD__ . ' ' . $order->getId() .  ' ' . $oldStatus . ' -> ' . $order->getOrderStatus());
		}
		switch ($order->getOrderStatus())
		{
			case order_OrderService::IN_PROGRESS:
				try
				{
					$this->getTransactionManager()->beginTransaction();
					$productIdsToCompile = array();
					$globalProductsArray = array();
						
					foreach ($order->getLineArray() as $line)
					{
						if ($line instanceof order_persistentdocument_orderline)
						{
							$product = $line->getProduct();
							if ($product !== null && $line->getQuantity() > 0)
							{
								$productIdsToCompile[$product->getId()] = true;
								$properties = $line->getGlobalPropertyArray();
								$product->getDocumentService()->updateProductFromCartProperties($product, $properties);
								$this->buildProductList($product, $line->getQuantity(), $globalProductsArray);
							}
						}
					}
						
					foreach ($globalProductsArray as $productInfo)
					{
						list($product, $quantity) = $productInfo;
						$productId = $product->getId();
						$productIdsToCompile[$productId] = true;
						$this->increaseQuantity($product, -$quantity, $order);
					}
						
					if (count($productIdsToCompile))
					{
						catalog_ProductService::getInstance()->setNeedCompile(array_keys($productIdsToCompile));
					}
					$this->getTransactionManager()->commit();
				}
				catch (Exception $e)
				{
					$this->getTransactionManager()->rollBack($e);
				}
				break;
			case order_OrderService::CANCELED:
				if ($oldStatus == order_OrderService::IN_PROGRESS || $oldStatus == order_OrderService::COMPLETE)
				{
					try
					{
						$this->getTransactionManager()->beginTransaction();
						$productIdsToCompile = array();
						$globalProductsArray = array();

						foreach ($order->getLineArray() as $line)
						{
							if ($line instanceof order_persistentdocument_orderline)
							{
								$product = $line->getProduct();
								if ($product !== null && $line->getQuantity() > 0)
								{
									$productIdsToCompile[$product->getId()] = true;
									$properties = $line->getGlobalPropertyArray();
									$product->getDocumentService()->updateProductFromCartProperties($product, $properties);
									$this->buildProductList($product, $line->getQuantity(), $globalProductsArray);

								}
							}
						}

						foreach ($globalProductsArray as $productInfo)
						{
							list($product, $quantity) = $productInfo;
							$productId = $product->getId();
							$productIdsToCompile[$productId] = $quantity;
							$this->increaseQuantity($product, $quantity, $order);
						}

						if (count($productIdsToCompile))
						{
							catalog_ProductService::getInstance()->setNeedCompile(array_keys($productIdsToCompile));
						}
							
						$this->getTransactionManager()->commit();
					}
					catch (Exception $e)
					{
						$this->getTransactionManager()->rollBack($e);
					}
				}
				break;
		}
	}

	/**
	 * @param catalog_persistentdocument_product $document
	 * @param double $nb
	 * @param order_persistentdocument_order $order
	 * @return double new quantity
	 */
	protected function increaseQuantity($document, $nb, $order)
	{
		$result = null;
		$stDoc = $this->getStockableDocument($document);
		if ($stDoc instanceof catalog_persistentdocument_product)
		{
			$result = $stDoc->addStockQuantity($nb);
		}
		return $result;
	}

	/**
	 * @param catalog_persistentdocument_product $document
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
	 * @param users_persistentdocument_backenduser[] $recipients
	 * @param catalog_persistentdocument_product $document
	 * @return boolean
	 */
	protected function sendStockAlertNotification($users, $document)
	{
		$ns = notification_NotificationService::getInstance();
		// Products and backendusers have no website or lang...
		$configuredNotif = $ns->getConfiguredByCodeName(self::STOCK_ALERT_NOTIFICATION_CODENAME);
		if ($configuredNotif instanceof notification_persistentdocument_notification)
		{
			$configuredNotif->setSendingModuleName('catalog');
			$emailAddressArray = array();
			foreach ($users as $user)
			{
				$emailAddressArray[] = $user->getEmail();
			}
			$recipients = new mail_MessageRecipients($emailAddressArray);
			return $ns->sendNotificationCallback($configuredNotif, $recipients, array($this, 'getStockAlertNotifParameters'), $document);
		}
		return true;
	}

	/**
	 * @param catalog_persistentdocument_product $product
	 * @return array
	 */
	public function getStockAlertNotifParameters($product)
	{
		return array(
			'label' => $product->getLabelAsHtml(),
			'threshold' => $this->getAlertThreshold($product)
		);
	}
	
	/**
	 * @param catalog_persistentdocument_product $document
	 * @param string[] $allowedProperties
	 * @param array $data
	 */
	public function loadStockInfo($document, $allowedProperties, &$data)
	{
		if ($document instanceof catalog_persistentdocument_product && in_array('stockQttJSON', $allowedProperties)) 
		{
			$infos = array();
			$infos[] = array('stocklabel' => LocaleService::getInstance()->transBO('m.catalog.bo.doceditor.master', array('ucf')), 
				'stockQuantity' => $document->getStockQuantity() === null ? -1 : $document->getStockQuantity(), 
				'stockAlertThreshold' => $document->getStockAlertThreshold() === null ? -1 : $document->getStockAlertThreshold());
			
			$data['stockQttJSON'] = JsonService::getInstance()->encode($infos);
		}
	}
	
	/**
	 * @param catalog_persistentdocument_product $document
	 * @param string[] $propertiesValue
	 */	
	public function updateStockInfo($document, $propertiesValue)
	{
		if ($document instanceof catalog_persistentdocument_product && isset($propertiesValue['stockQttJSON']))
		{
			$infos = JsonService::getInstance()->decode($propertiesValue['stockQttJSON']);
			if (is_array($infos))
			{
				$stockQuantity = $infos[0]['stockQuantity'];
				$stockQuantity =  ($stockQuantity === '' || $stockQuantity === '-1') ? null : intval($stockQuantity);
				$document->setStockQuantity($stockQuantity);
				
				$stockAlertThreshold = $infos[0]['stockAlertThreshold'];
				$stockAlertThreshold =  ($stockQuantity === null || $stockAlertThreshold === '' || $stockAlertThreshold === '-1') ? null : intval($stockAlertThreshold);
				$document->setStockAlertThreshold($stockAlertThreshold);
				$document->save();
			}
		} 
	}

	/**
	 * @param catalog_persistentdocument_product $document
	 * @return Double
	 */
	protected function getAlertThreshold($document)
	{
		$threshold = $document->getStockAlertThreshold();
		if ($threshold === null)
		{
			return ModuleService::getInstance()->getPreferenceValue('catalog', 'stockAlertThreshold');
		}
		return $threshold;
	}
}
