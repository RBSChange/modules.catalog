<?php
/**
 * catalog_PriceService
 * @package modules.catalog
 */
class catalog_PriceService extends f_persistentdocument_DocumentService
{
	/**
	 * Singleton
	 * @var catalog_PriceService
	 */
	private static $instance = null;

	/**
	 * @return catalog_PriceService
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
	 * @return catalog_persistentdocument_price
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_catalog/price');
	}

	/**
	 * Create a query based on 'modules_catalog/price' model
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_catalog/price');
	}

	/**
	 * @param catalog_persistentdocument_product $product
	 * @param catalog_persistentdocument_shop $shop
	 * @param customer_persistentdocument_customer $customer
	 * @param Double $quantity
	 * @return catalog_persistentdocument_price
	 */
	public function getPrice($product, $shop, $customer, $quantity = 1)
	{
		$targetIds = array(0);
		if ($customer !== null)
		{
			$targetIds[] = $customer->getId();
			$tarifGroup = $customer->getTarifGroup();
			if ($customer->getTarifGroup() !== null)
			{
				$targetIds[] = $tarifGroup->getId();
			}
		}
		$query = $this->createQuery()
			->add(Restrictions::published())
			->add(Restrictions::eq('productId', $product->getId()))
			->add(Restrictions::eq('shopId', $shop->getId()))
			->add(Restrictions::in('targetId', $targetIds))
			->add(Restrictions::le('thresholdMin', $quantity))
			->add(Restrictions::gt('thresholdMax', $quantity))
			->addOrder(Order::desc('priority'))
			->setFirstResult(0)
			->setMaxResults(1);
		return f_util_ArrayUtils::firstElement($query->find());
	}

	/**
	 * @param catalog_persistentdocument_product $product
	 * @param catalog_persistentdocument_shop $shop
	 * @param customer_persistentdocument_customer $customer
	 * @return catalog_persistentdocument_price[]
	 */
	public function getPrices($product, $shop, $customer)
	{
		$targetIds = array(0);
		if ($customer !== null)
		{
			$targetIds[] = $customer->getId();
			$tarifGroup = $customer->getTarifGroup();
			if ($customer->getTarifGroup() !== null)
			{
				$targetIds[] = $tarifGroup->getId();
			}
		}
		$prices = $this->createQuery()
			->add(Restrictions::published())
			->add(Restrictions::eq('productId', $product->getId()))
			->add(Restrictions::eq('shopId', $shop->getId()))
			->add(Restrictions::in('targetId', $targetIds))
			->addOrder(Order::asc('thresholdMin'))->addOrder(Order::asc('priority'))->find();
		$result = array();
		foreach ($prices as $price)
		{
			$result[$price->getThresholdMin()] = $price;
		}
		return $result;
	}
	
	/**
	 * @param String $date
	 * @param Integer $productId
	 * @param Integer $shopId
	 * @param String $orderBy
	 * @param String $orderDir
	 * @param Integer $targetId
	 */
	public function getPricesForDate($date, $productId, $shopId, $orderBy = 'thresholdmin', $orderDir = 'asc', $targetId = null)
	{
		$query = $this->createQuery()
			->add(Restrictions::eq('productId', $productId))
			->add(Restrictions::eq('shopId', $shopId))
			->add(Restrictions::orExp(Restrictions::isNull('startpublicationdate'), Restrictions::le('startpublicationdate', $date)))
			->add(Restrictions::orExp(Restrictions::isNull('endpublicationdate'), Restrictions::gt('endpublicationdate', $date)));
			
		if ($targetId !== null)
		{
			$query->add(Restrictions::eq('targetId', $targetId));
		}
		
		$model = f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_catalog/price');
		$propertyInfo = $model->getProperty($orderBy);
		if ($propertyInfo)
		{
			if ($orderDir == 'desc')
			{
				if ($propertyInfo->isString())
				{
					$query->addOrder(Order::idesc($orderBy));
				}
				else
				{
					$query->addOrder(Order::desc($orderBy));
				}
			}
			else
			{
				if ($propertyInfo->isString())
				{
					$query->addOrder(Order::iasc($orderBy));
				}
				else
				{
					$query->addOrder(Order::asc($orderBy));
				}
			}
		}
		return $query->find();
	}

	/**
	 * @param String $targetType
	 * @return Boolean
	 */
	public function canChooseTarget($targetType)
	{
		switch ($targetType)
		{
			case 'customer' :
			case 'group' :
				return true;
		
			default :
				return false;
		}
	}

	/**
	 * @param String $targetType
	 * @param Integer $shopId
	 * @return f_persistentdocument_PersistentDocument
	 */
	public function getAvailableTargets($targetType, $shopId)
	{
		$shop = DocumentHelper::getDocumentInstance($shopId);
		$targets = array();
		switch ($targetType)
		{
			case 'customer' :
				if (catalog_ModuleService::areCustomersEnabled())
				{
					$targets = customer_CustomerService::getInstance()->createQuery()->add(Restrictions::eq('user.websiteid', $shop->getWebsite()->getId()))->find();
				}
				break;
				
			case 'group' :
				if (catalog_ModuleService::areCustomersEnabled())
				{
					$targets = customer_TarifcustomergroupService::getInstance()->createQuery()->find();
				}
				break;
		}
		return $targets;
	}
	
	/**
	 * @param Double $value
	 * @param catalog_persistentdocument_shop $shop
	 * @return String
	 */
	public static function formatValue($value, $shop)
	{
		return catalog_PriceHelper::applyFormat($value, $shop->getDocumentService()->getPriceFormat($shop));
	}
		
	// Protected methods.
	
	/**
	 * @param catalog_persistentdocument_price $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
	protected function preSave($document, $parentNodeId)
	{
		if ($document->getProductId() === null)
		{
			$document->setProductId($parentNodeId);
		}
		
		if ($document->isPropertyModified('targetId'))
		{
			$this->refreshPriority($document);
		}
		
		if ($document->getThresholdMin() === null || $document->getThresholdMin() < 1)
		{
			$document->setThresholdMin(1);
		}
		
		if ($document->getLabel() == '' || $document->isPropertyModified('targetId'))
		{
			$this->refreshLabel($document);
		}
		
		// Update thresholdMax value.
		$query = $this->createQuery()
			->add(Restrictions::eq('productId', $document->getProductId()))
			->add(Restrictions::eq('shopId', $document->getShopId()))
			->add(Restrictions::eq('targetId', $document->getTargetId()))
			->add(Restrictions::gt('thresholdMin', $document->getThresholdMin()))
			->add(Restrictions::ne('id', $document->getId()))
			->addOrder(Order::asc('thresholdMin'))
			->setFirstResult(0)
			->setMaxResults(1);
		$otherPrice = f_util_ArrayUtils::firstElement($query->find());
		$document->setThresholdMax(($otherPrice !== null) ? $otherPrice->getThresholdMin() : PHP_INT_MAX);
	}

	/**
	 * @param catalog_persistentdocument_price $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
	protected function postSave($document, $parentNodeId)
	{
		$query = $this->createQuery()
			->add(Restrictions::eq('productId', $document->getProductId()))
			->add(Restrictions::eq('shopId', $document->getShopId()))
			->add(Restrictions::eq('targetId', $document->getTargetId()))
			->add(Restrictions::eq('thresholdMin', $document->getThresholdMin()))
			->add(Restrictions::ne('id', $document->getId()));
		$endDate = $document->getEndpublicationdate();
		if (!is_null($endDate))
		{
			$query->add(Restrictions::orExp(Restrictions::isEmpty('startpublicationdate'), Restrictions::lt('startpublicationdate', $endDate)));
		}
		$startDate = $document->getStartpublicationdate();
		if (!is_null($startDate))
		{
			$query->add(Restrictions::orExp(Restrictions::isEmpty('endpublicationdate'), Restrictions::gt('endpublicationdate', $startDate)));
		}
		
		if ($query->findUnique() !== null)
		{
			throw new BaseException('A single product can have only one price for a triplet of shopId, targetId and thresholdMin on a given publication period.', 'modules.catalog.document.price.exception.Trying-making-duplicate');
		}
		
		// Update thresholdMax for the price with a thresholdMin directly inferior.
		$query = $this->createQuery()
			->add(Restrictions::eq('productId', $document->getProductId()))
			->add(Restrictions::eq('shopId', $document->getShopId()))
			->add(Restrictions::eq('targetId', $document->getTargetId()))
			->add(Restrictions::lt('thresholdMin', $document->getThresholdMin()))
			->add(Restrictions::gt('thresholdMax', $document->getThresholdMin()));
		$priceToUpdate = $query->findUnique();
		if ($priceToUpdate !== null)
		{
			$priceToUpdate->setThresholdMax($document->getThresholdMin());
			$priceToUpdate->save();
		}
		
		catalog_ProductService::getInstance()->setNeedCompileForPrice($document);
	}
	
	/**
	 * @param catalog_persistentdocument_productdeclination $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal).
	 * @return void
	 */
	protected function preInsert($document, $parentNodeId)
	{
		parent::preInsert($document, $parentNodeId);		
		$document->setInsertInTree(false);
	}
	
	/**
	 * @param catalog_persistentdocument_price $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal).
	 * @return void
	 */
	protected function postInsert($document, $parentNodeId)
	{
		if ($document->isPublished())
		{
			$product = $document->getProduct();
			$product->getDocumentService()->publishIfPossible($product->getId());
		}
	}
	
	/**
	 * @param catalog_persistentdocument_price $document
	 * @param String $oldPublicationStatus
	 * @return void
	 */
	protected function publicationStatusChanged($document, $oldPublicationStatus, $params)
	{
		if ($document->getProductId())
		{
			if (!isset($params['cause']) || $params["cause"] != "delete")
			{
				if ($document->isPublished() || $oldPublicationStatus === 'PUBLICATED')
				{
					catalog_ProductService::getInstance()->setNeedCompileForPrice($document);
				}
			}
		}
	}
	
	/**
	 * @param catalog_persistentdocument_price $document
	 * @return void
	 */
	protected function postDelete($document)
	{
		if ($document->getProductId())
		{
			// Update thresholdMax for the price with a thresholdMin directly inferior.
			$query = $this->createQuery()
				->add(Restrictions::eq('productId', $document->getProductId()))
				->add(Restrictions::eq('shopId', $document->getShopId()))
				->add(Restrictions::eq('targetId', $document->getTargetId()))
				->add(Restrictions::eq('thresholdMax', $document->getThresholdMin()));
			$priceToUpdate = $query->findUnique();
			if ($priceToUpdate !== null)
			{
				$priceToUpdate->setThresholdMax($document->getThresholdMax());
				$priceToUpdate->save();
			}
		
			catalog_ProductService::getInstance()->setNeedCompileForPrice($document);
		}
	}
	
	/**
	 * @param integer $productId
	 */
	public function deleteForProductId($productId)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . ' START for Product id:' . $productId);
		}
		$prices = $this->createQuery()->add(Restrictions::eq('productId', $productId))->find();
		if (count($prices))
		{
			$priceSrv = catalog_PriceService::getInstance();
			foreach ($prices as $price) 
			{
				$price->setProductId(null);
				$priceSrv->delete($price);
			}
		}
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . ' END for Product id:' . $productId);
		}
	}
	
	/**
	 * @param catalog_persistentdocument_price $document
	 * @return void
	 */
	protected function refreshLabel($document)
	{
		$targetId = $document->getTargetId();
		if ($targetId == 0)
		{
			$document->setLabel(f_Locale::translate('&modules.catalog.document.price.Shop-price;'));
		}
		else
		{
			$target = DocumentHelper::getDocumentInstance($targetId);
			$replacements = array('name' => $target->getLabel());
			if ($target instanceof customer_persistentdocument_customer)
			{
				$document->setLabel(f_Locale::translate('&modules.catalog.document.price.Customer-price;', $replacements));
			}
			else
			{
				$document->setLabel(f_Locale::translate('&modules.catalog.document.price.Target-price;', $replacements));
			}
		}
	}
	
	/**
	 * @param catalog_persistentdocument_price $document
	 * @return void
	 */
	protected function refreshPriority($document)
	{
		$targetId = $document->getTargetId();
		if ($targetId == 0)
		{
			$document->setPriority(25);
		}
		else
		{
			$target = DocumentHelper::getDocumentInstance($targetId);
			if ($target instanceof customer_persistentdocument_customer)
			{
				$document->setPriority(75);
			}
			else if ($target instanceof customer_persistentdocument_tarifcustomergroup)
			{
				$document->setPriority(50);
			}
			else 
			{
				throw new BaseException('Unexpected target!', 'modules.catalog.document.price.Unexpected-target-error');
			}
		}
	}
}