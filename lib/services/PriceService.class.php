<?php
/**
 * catalog_PriceService
 * @package modules.catalog
 */
class catalog_PriceService extends f_persistentdocument_DocumentService
{
	const META_IS_REPLICATED = 'modules.catalog.isReplicated';
	
	const PRIORITY_AUTO = 0;
	const PRIORITY_SHOP = 25;
	const PRIORITY_TARIFGROUP = 50;
	const PRIORITY_CUSTOMER = 75;
	const PRIORITY_PRODUCT = 100;
	
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
	 * @param customer_persistentdocument_customer $customer
	 * @return integer[]
	 */
	public function convertCustomerToTargetIds($customer)
	{
		$targetIds = array(0);
		if ($customer instanceof customer_persistentdocument_customer)
		{
			$targetIds[] = $customer->getId();
			$tarifGroup = $customer->getTarifGroup();
			if ($customer->getTarifGroup() !== null)
			{
				$targetIds[] = $tarifGroup->getId();
			}
		}
		else if ($customer !== null)
		{
			throw new Exception('Invalid customer parameter ' . var_export($customer, true));
		}
		return $targetIds;
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
		$targetIds = $this->convertCustomerToTargetIds($customer);
		return $this->getPriceByTargetIds($product, $shop, $targetIds, $quantity);
	}

	/**
	 * @param catalog_persistentdocument_product $product
	 * @param catalog_persistentdocument_shop $shop
	 * @param integer[] $targetIds
	 * @param Double $quantity
	 * @return catalog_persistentdocument_price
	 */
	public function getPriceByTargetIds($product, $shop, $targetIds, $quantity = 1)
	{
		$query = $this->createQuery()
			->add(Restrictions::published())
			->add(Restrictions::eq('productId', $product->getId()))
			->add(Restrictions::eq('shopId', $shop->getId()))
			->add(Restrictions::le('thresholdMin', $quantity))
			->add(Restrictions::in('targetId', $targetIds))
			->addOrder(Order::desc('priority'))
			->addOrder(Order::desc('thresholdMin'))
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
		$targetIds = $this->convertCustomerToTargetIds($customer);
		return $this->getPricesByTargetIds($product, $shop, $targetIds);
	}

	/**
	 * @param catalog_persistentdocument_product $product
	 * @param catalog_persistentdocument_shop $shop
	 * @param integer[] $targetIds
	 * @return catalog_persistentdocument_price[]
	 */
	public function getPricesByTargetIds($product, $shop, $targetIds)
	{
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
	 * @param String $date date can be null. If null, result is limited to 100 rows.
	 * @param Integer $productId
	 * @param Integer $shopId
	 * @param String $orderBy
	 * @param String $orderDir
	 * @param Integer $targetId
	 */
	public function getPricesForDate($date, $productId, $shopId, $targetId = null)
	{
		$query = $this->createQuery()
			->add(Restrictions::eq('productId', $productId))
			->add(Restrictions::eq('shopId', $shopId));
		if ($date !== null)
		{
			$startDate = date_Converter::convertDateToGMT(date_Calendar::getInstance($date)->toMidnight())->toString();
			$endDate = date_Calendar::getInstance($startDate)->add(date_Calendar::DAY, 1)->toString();
			$query->add(Restrictions::orExp(Restrictions::isNull('startpublicationdate'), Restrictions::lt('startpublicationdate', $endDate)))
			->add(Restrictions::orExp(Restrictions::isNull('endpublicationdate'), Restrictions::gt('endpublicationdate', $startDate)));

			$query->setMaxResults(100);
		}
			
		if ($targetId !== null)
		{
			$query->add(Restrictions::eq('targetId', $targetId));
		}
		$query->addOrder(Order::desc('priority'));
		$query->addOrder(Order::desc('publicationstatus'));
		return $query->find();
	}
	
	/**
	 * @param catalog_persistentdocument_price $price
	 * @param array $array
	 */
	public function transformToArray($price, &$array)
	{
		$isLocked = ($price instanceof catalog_persistentdocument_lockedprice);
		$array[] = array(
			'id' => $price->getId(),
			'status' => $price->getPublicationstatus(),
			'boModel' => $price->getPersistentModel()->getBackofficeName(),
			'priceType' => str_replace('/', '_', $price->getDocumentModelName()),
			'value' => $price->getValueWithTax(),
			'valueWithTax' => $price->getFormattedValueWithTax(),
			'isDiscount' => $price->isDiscount(),
			'isDiscountLabel' => f_Locale::translateUI('&modules.uixul.bo.general.' . ($price->isDiscount() ? 'Yes' : 'No') . ';'),
			'allowDeletion' => !$isLocked,
			'allowDiscountCreation' => !$isLocked && !$price->isDiscount(),
			'allowDiscountRemoval' => !$isLocked && $price->isDiscount(),
			'tagrgetId' => $price->getTargetId(),
			'targetLabel' => $price->getTargetLabel(),
			'priority' => $price->getPriority(),
			'thresholdMin' => $price->getThresholdMin(),
			'startpublicationdate' => $price->getUIStartpublicationdate(),
			'endpublicationdate' => $price->getUIEndpublicationdate(),
			'actionrow' => true
		);
	}

	/**
	 * @param catalog_persistentdocument_product $product
	 * @return array
	 */
	public function getAvailableTargetTypes($product = null)
	{
		$result = array('all' => array('label' => f_Locale::translateUI('&modules.catalog.bo.doceditor.panel.prices.Type-all;')),
					'shop' =>  array('label' => f_Locale::translateUI('&modules.catalog.bo.doceditor.panel.prices.Type-shop;')));
		
		if (catalog_ModuleService::areCustomersEnabled())
		{
			$result['group'] = array('label' => f_Locale::translateUI('&modules.catalog.bo.doceditor.panel.prices.Type-group;'));
			$result['customer'] = array('label' => f_Locale::translateUI('&modules.catalog.bo.doceditor.panel.prices.Type-customer;'));
		}

		$result['kit'] = array('label' => f_Locale::translateUI('&modules.catalog.bo.doceditor.panel.prices.Type-kit;'));
		return $result;
	}
	
	/**
	 * @param String $targetType
	 * @param Integer $shopId
	 * @return array
	 */
	public function getTargetInfo($targetType, $shopId)
	{
		switch ($targetType)
		{
			case 'customer' :
				if (catalog_ModuleService::areCustomersEnabled())
				{
					return array("type" => "document", "allow" => "modules_customer_customer", "moduleselector" => "customer");	
				}
				break;
			case 'group' :
				if (catalog_ModuleService::areCustomersEnabled())
				{
					return array("type" => "document", "allow" => "modules_customer_tarifcustomergroup", "moduleselector" => "customer");
				}
				break;
			case 'kit' :
				return array("type" => "document", "allow" => "modules_catalog_kit", "moduleselector" => "catalog");
				break;
			case 'all' :
				break;
			case 'shop' :
				$shop = DocumentHelper::getDocumentInstance($shopId);
				return array("type" => "dropdown", "options" => array(array('id' => 0, 'label' => $shop->getLabel())));
				break;
		}
		return null;
	}
	
	/**
	 * @param String $targetType
	 * @param catalog_persistentdocument_product $product
	 * @param Integer $shopId
	 * @return array
	 */
	public function getAvailableTargets($targetType, $product, $shopId)
	{
		$shop = DocumentHelper::getDocumentInstance($shopId);
		$targets = array();
		switch ($targetType)
		{
			case 'customer' :
				if (catalog_ModuleService::areCustomersEnabled())
				{
					$targets = customer_CustomerService::getInstance()->createQuery()
						->add(Restrictions::eq('user.websiteid', $shop->getWebsite()->getId()))
						->setProjection(Projections::property('id'), Projections::property('label'))
						->find();
				}
				break;				
			case 'group' :
				if (catalog_ModuleService::areCustomersEnabled())
				{
					$targets = customer_TarifcustomergroupService::getInstance()->createQuery()
					->setProjection(Projections::property('id'), Projections::property('label'))
					->find();
				}
				break;
			case 'kit':
				$targets = catalog_KitService::getInstance()->createQuery()
					->add(Restrictions::eq('kititem.product', $product))
					->setProjection(Projections::property('id'), Projections::property('label'))
					->find();
				break;
			case 'all':
				break;
			case 'shop':
				$targets[] = array('id' => 0, 'label' => $shop->getLabel());
				break;
		}
		$result = array();
		foreach ($targets as $target) 
		{
			$result[$target['id']] = array('label' => $target['label']);
		}
		return $result;
	}
	
	/**
	 * @param Double $value
	 * @param catalog_persistentdocument_shop $shop
	 * @return String
	 * @deprecated
	 */
	public static function formatValue($value, $shop)
	{
		Framework::warn(__METHOD__ . ' is deprecated');
		return $shop->formatPrice($value);
	}
		
	// Protected methods.
	
	/**
	 * @param catalog_persistentdocument_price $price
	 */
	protected function hasCustomPriority($price)
	{
		$priority = $price->getPriority();
		if ($priority == self::PRIORITY_AUTO || 
			$priority == self::PRIORITY_SHOP || 
			$priority == self::PRIORITY_TARIFGROUP || 
			$priority == self::PRIORITY_CUSTOMER)
		{
			return false;
		}
		return true;
	}
	
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
		
		if ($document->isPropertyModified('targetId') && !$this->hasCustomPriority($document))
		{
			$this->refreshPriority($document);
		}
		
		if ($document->getThresholdMin() === null || $document->getThresholdMin() < 1)
		{
			$document->setThresholdMin(1);
		}
		
		if (f_util_StringUtils::isEmpty($document->getLabel()) || $document->isPropertyModified('targetId'))
		{
			$this->refreshLabel($document);
		}
	}
	
	/**
	 * @param catalog_persistentdocument_price $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
	protected function postSave($document, $parentNodeId)
	{
		$this->checkConflicts($document);
		
		catalog_ProductService::getInstance()->setNeedCompileForPrice($document);
	}
	
	/**
	 * @param catalog_persistentdocument_price $document
	 * @throws BaseException
	 */
	protected function checkConflicts($document)
	{
		$query = $this->createQuery()
			->add(Restrictions::eq('productId', $document->getProductId()))
			->add(Restrictions::eq('shopId', $document->getShopId()))
			->add(Restrictions::eq('targetId', $document->getTargetId()))
			->add(Restrictions::eq('thresholdMin', $document->getThresholdMin()))
			->add(Restrictions::eq('priority', $document->getPriority()))
			->add(Restrictions::eq('currencyId', $document->getCurrencyId()))
			->add(Restrictions::ne('id', $document->getId()));
			
		$endDate = $document->getEndpublicationdate();
		if ($endDate !== null)
		{
			$query->add(Restrictions::orExp(Restrictions::isEmpty('startpublicationdate'), Restrictions::lt('startpublicationdate', $endDate)));
		}
		$startDate = $document->getStartpublicationdate();
		if ($startDate !== null)
		{
			$query->add(Restrictions::orExp(Restrictions::isEmpty('endpublicationdate'), Restrictions::gt('endpublicationdate', $startDate)));
		}
		
		if ($query->findUnique() !== null)
		{
			throw new BaseException('A single product can have only one price for a triplet of shopId, targetId and thresholdMin on a given publication period.', 'modules.catalog.document.price.exception.Trying-making-duplicate');
		}
	}
	
	/**
	 * @param catalog_persistentdocument_price $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal).
	 * @return void
	 */
	protected function preInsert($document, $parentNodeId)
	{
		parent::preInsert($document, $parentNodeId);		
		$document->setInsertInTree(false);
		$currency = catalog_CurrencyService::getInstance()->getByCode($document->getShop()->getCurrencyCode());
		if ($currency === null)
		{
			throw new Exception("Invalid currency for price");
		}
		$document->setCurrencyId($currency->getId());
	}
	
	/**
	 * @param catalog_persistentdocument_price $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal).
	 * @return void
	 */
	protected function postInsert($document, $parentNodeId)
	{
		$product = $document->getAttachedDocument();
		if ($product instanceof f_persistentdocument_PersistentDocument)
		{
			$ds = $product->getDocumentService();
			if (f_util_ClassUtils::methodExists($ds, 'priceAdded'))
			{
				$ds->priceAdded($product, $document);
			}
		}
	}
	
	/**
	 * @param catalog_persistentdocument_price $document
	 */	
	protected function preDelete($document)
	{
		$product = $document->getAttachedDocument();
		if ($product instanceof f_persistentdocument_PersistentDocument)
		{
			$ds = $product->getDocumentService();
			if (f_util_ClassUtils::methodExists($ds, 'priceRemoved'))
			{
				$ds->priceRemoved($product, $document);
			}
		}		
	}
	
	/**
	 * @var string[]
	 */
	private $ignoredProperties = array('modificationdate', 'documentversion', 'metastring');
	
	/**
	 * @param catalog_persistentdocument_price $document
	 * @param Integer $parentNodeId
	 */
	protected function postUpdate($document, $parentNodeId)
	{
		// If this price is replicated, synchronize the replicated prices values. 
		if ($document->hasMeta(self::META_IS_REPLICATED))
		{
			$replicatedProperties = array();
			foreach ($document->getModifiedPropertyNames() as $propertyName)
			{
				if (!in_array($propertyName, $this->ignoredProperties))
				{
					$replicatedProperties['set'.ucfirst($propertyName)] = 'get'.ucfirst($propertyName);
				}
			}
			
			$query = catalog_LockedpriceService::getInstance()->createQuery()
				->add(Restrictions::eq('lockedFor', $document->getId()));
			foreach ($query->find() as $price)
			{
				foreach ($replicatedProperties as $setter => $getter)
				{
					$args = array(f_util_ClassUtils::callMethodOn($document, $getter));
					f_util_ClassUtils::callMethodArgsOn($price, $setter, $args);
				}
				$price->setIgnoreConflicts(true);
				$price->save();
			}
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
			catalog_ProductService::getInstance()->setNeedCompileForPrice($document);
		}
		
		// Delete all prices replicated from this one.
		if ($document->hasMeta(self::META_IS_REPLICATED))
		{
			catalog_LockedpriceService::getInstance()->createQuery()
				->add(Restrictions::eq('lockedFor', $document->getId()))->delete();
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
			$document->setPriority(self::PRIORITY_SHOP);
		}
		else
		{
			$target = DocumentHelper::getDocumentInstance($targetId);
			if ($target instanceof customer_persistentdocument_customer)
			{
				$document->setPriority(self::PRIORITY_CUSTOMER);
			}
			else if ($target instanceof customer_persistentdocument_tarifcustomergroup)
			{
				$document->setPriority(self::PRIORITY_TARIFGROUP);
			}
			else if ($target instanceof catalog_persistentdocument_product)
			{
				$document->setPriority(self::PRIORITY_PRODUCT);
			}
			else
			{
				throw new BaseException('Unexpected target!', 'modules.catalog.document.price.Unexpected-target-error');
			}
		}
	}
	
	/**
	 * @param catalog_persistentdocument_lockedprice $price
	 * @param catalog_persistentdocument_price $originalPrice
	 */
	public function convertToNotReplicated($price, $originalPrice)
	{
		$this->transform($price, 'modules_catalog/price');
	}
	
	/**
	 * @param catalog_persistentdocument_price $price
	 * @param double $value
	 * @param integer $detail
	 * @param string $start
	 * @param string $end
	 */
	public function createDiscountOnPrice($price, $value, $detail, $start, $end)
	{
		try 
		{
			$this->tm->beginTransaction();
			
			$pricesToSave = array($price);
			if ($start == $price->getStartpublicationdate() && $end == $price->getEndpublicationdate())
			{
				$discountPrice = $price;
			}
			else 
			{
				$discountPrice = $this->clonePrice($price);
				$discountPrice->setStartpublicationdate($start);
				$discountPrice->setEndpublicationdate($end);
				$pricesToSave[] = $discountPrice;
				
				if ($start != $price->getStartpublicationdate())
				{
					if ($end != $price->getEndpublicationdate())
					{
						$thirdPrice = $this->clonePrice($price);
						$thirdPrice->setStartpublicationdate($end);
						$pricesToSave[] = $thirdPrice;
					}
					$price->setEndpublicationdate($start);
				}
				else 
				{
					$price->setStartpublicationdate($end);
				}
			}
			$discountPrice->setDiscountValue($value);
			$discountPrice->setDiscountDetail($detail);
						
			foreach ($pricesToSave as $price)
			{
				$price->save();
			}
			
			$this->tm->commit();
		}
		catch (Exception $e)
		{
			$this->tm->rollBack($e);
			throw $e;
		}
	}
	
	/**
	 * @param catalog_persistentdocument_price $price
	 * @return catalog_persistentdocument_lockedprice
	 */
	protected function clonePrice($price)
	{
		$newPrice = $this->getNewDocumentInstance();
		$price->copyPropertiesTo($newPrice, true);
		$newPrice->setMeta("f_tags", array());
		$newPrice->setAuthor(null);
		$newPrice->setAuthorid(null);
		$newPrice->setCreationdate(null);
		$newPrice->setModificationdate(null);
		$newPrice->setDocumentversion(0);
		return $newPrice;
	}
	
	/**
	 * @param catalog_persistentdocument_price $price
	 * @param double $value
	 * @param integer $detail
	 * @param string $start
	 * @param string $end
	 */
	public function removeDiscountFromPrice($price)
	{
		$price->setValueWithoutTax($price->getOldValueWithoutTax());
		$price->setOldValueWithoutTax(null);
		$price->setDiscountDetail(null);
		$price->save();
	}
	
	/**
	 * @param catalog_persistentdocument_price $price1
	 * @param catalog_persistentdocument_price $price2
	 * @return catalog_persistentdocument_price
	 */
	public function getPriceDifference($price1, $price2)
	{
		$diff = $this->clonePrice($price1);
		$diff->setValueWithoutTax($price1->getValueWithoutTax() - $price2->getValueWithoutTax());
		$diff->setOldValueWithoutTax(null);
		return $diff;
	}
	
	/**
	 * @param catalog_persistentdocument_price $tmpPriceInfo
	 * @return catalog_persistentdocument_price
	 */	
	public function insertPrice($tmpPriceInfo)
	{
		if (!$tmpPriceInfo->isNew())
		{
			throw new Exception("tmpPriceInfo is not a temporary price: " . $tmpPriceInfo->getId());
		}		
		$startDate = $tmpPriceInfo->getStartpublicationdate();
		$endDate = $tmpPriceInfo->getEndpublicationdate();
		
		try 
		{
			$this->tm->beginTransaction();	
				
			$query = $this->createQuery()
				->add(Restrictions::eq('productId', $tmpPriceInfo->getProductId()))
				->add(Restrictions::eq('shopId', $tmpPriceInfo->getShopId()))
				->add(Restrictions::eq('targetId', $tmpPriceInfo->getTargetId()))
				->add(Restrictions::eq('priority', $tmpPriceInfo->getPriority()))
				->add(Restrictions::eq('thresholdMin', $tmpPriceInfo->getThresholdMin()));
				
			if ($startDate !== null)
			{
				$query->add(Restrictions::orExp(Restrictions::isNull('endpublicationdate'), Restrictions::gt('endpublicationdate', $startDate)));
			}
			
			if ($endDate !== null)
			{
				$query->add(Restrictions::orExp(Restrictions::isNull('startpublicationdate'), Restrictions::lt('startpublicationdate', $endDate)));
			}
			$query->addOrder(Order::asc('startpublicationdate'));
			$prices = $query->find();
	
			$result = $this->insertPriceInternal($tmpPriceInfo, $prices);
			$this->tm->commit();
		}
		catch (Exception $e)
		{
			$this->tm->rollBack($e);
			throw $e;
		}
		return $result;
	}

	/**
	 * @param catalog_persistentdocument_price $tmpPriceInfo
	 * @param catalog_persistentdocument_price[] prices
	 * @return catalog_persistentdocument_price
	 */
	private function insertPriceInternal($tmpPriceInfo, $prices)
	{
		$nbPrices = count($prices);
		if ($nbPrices == 0)
		{
			$tmpPriceInfo->save();
			return $tmpPriceInfo;
		}
		
		$firstPrice = $prices[0];
		$lastPrice = ($nbPrices > 1) ? $prices[$nbPrices - 1] : null;
		foreach ($prices as $toDelete) 
		{
			if ($toDelete === $lastPrice || $toDelete === $firstPrice) {continue;}	
			$toDelete->delete();
		}
		
		$compare = $this->compareDate($tmpPriceInfo, $firstPrice);
		$dataProperties = array('valueWithoutTax', 'oldValueWithoutTax', 
			'taxCategory', 'discountDetail', 'ecoTax', 'lockedFor');
		
		switch ($compare) 
		{
			case 'equal':
				$tmpPriceInfo->copyPropertiesListTo($firstPrice, $dataProperties, false);
				$firstPrice->save();
				return $firstPrice;
				
			case 'part':
				$lastPrice = $firstPrice->getDocumentService()->getNewDocumentInstance();
				$firstPrice->copyPropertiesTo($lastPrice, false);
				$firstPrice->setEndpublicationdate($tmpPriceInfo->getStartpublicationdate());
				$lastPrice->setStartpublicationdate($tmpPriceInfo->getEndpublicationdate());
				$firstPrice->save();
				$lastPrice->save();
				$tmpPriceInfo->save();
				return $tmpPriceInfo;
				
			case 'include':
				if ($lastPrice !== null)
				{
					if ($this->compareDate($tmpPriceInfo, $lastPrice) === 'endbefore') 
					{
						$lastPrice->setStartpublicationdate($tmpPriceInfo->getEndpublicationdate());
						$lastPrice->save();
					}
					else
					{
						$lastPrice->delete();
					}
				}
				$tmpPriceInfo->copyPropertiesListTo($firstPrice, $dataProperties, false);
				$firstPrice->setEndpublicationdate($tmpPriceInfo->getEndpublicationdate());
				$firstPrice->setStartpublicationdate($tmpPriceInfo->getStartpublicationdate());
				$firstPrice->save();
				return $firstPrice;
				
			case 'startafter':
				$firstPrice->setEndpublicationdate($tmpPriceInfo->getStartpublicationdate());
				$firstPrice->save();
				if ($lastPrice !== null)
				{
					if ($this->compareDate($tmpPriceInfo, $lastPrice) === 'endbefore') 
					{
						$lastPrice->setStartpublicationdate($tmpPriceInfo->getEndpublicationdate());
						$lastPrice->save();
					}
					else
					{
						$lastPrice->delete();
					}
				}
				$tmpPriceInfo->save();
				return $tmpPriceInfo;
				
			case 'endbefore':	
				$firstPrice->setStartpublicationdate($tmpPriceInfo->getEndpublicationdate());
				$firstPrice->save();
				
				$tmpPriceInfo->save();
				return $tmpPriceInfo;
		}
		throw new Exception('Unable to insert price:' . $compare);
	}
	
	/**
	 * @param integer $productId
	 * @param integer $shopId
	 * @param integer $targetId
	 * @param integer $thresholdMin
	 * @param integer $priority
	 * @param datetime $startDate
	 * @param datetime $endDate
	 * @return integer
	 */
	public function deletePrice($productId, $shopId, $targetId, $thresholdMin, $priority, $startDate, $endDate)
	{
		try 
		{
			$this->tm->beginTransaction();	
				
			$query = $this->createQuery()
				->add(Restrictions::eq('productId', $productId))
				->add(Restrictions::eq('shopId', $shopId))
				->add(Restrictions::eq('targetId', $targetId))
				->add(Restrictions::eq('priority', $priority))
				->add(Restrictions::eq('thresholdMin', $thresholdMin));
				
			if ($startDate !== null)
			{
				$query->add(Restrictions::orExp(Restrictions::isNull('endpublicationdate'), Restrictions::gt('endpublicationdate', $startDate)));
			}
			
			if ($endDate !== null)
			{
				$query->add(Restrictions::orExp(Restrictions::isNull('startpublicationdate'), Restrictions::lt('startpublicationdate', $endDate)));
			}
			$query->addOrder(Order::asc('startpublicationdate'));
			$prices = $query->find();
			
			$result = $this->deletePriceInternal($startDate, $endDate, $prices);

			$this->tm->commit();
		}
		catch (Exception $e)
		{
			$this->tm->rollBack($e);
			throw $e;
		}
		return $result;
	}
	
	/**
	 * @param datetime $startDate
	 * @param datetime $endDate
	 * @param catalog_persistentdocument_price[] prices
	 * @return integer
	 */
	private function deletePriceInternal($startDate, $endDate, $prices)
	{
		$nbPrices = count($prices);
		if ($nbPrices == 0)
		{
			return $nbPrices;
		}
		$result = 0;
		
		$firstPrice = $prices[0];
		$lastPrice = ($nbPrices > 1) ? $prices[$nbPrices - 1] : null;
		foreach ($prices as $toDelete) 
		{
			if ($toDelete === $lastPrice || $toDelete === $firstPrice) {continue;}
			$toDelete->delete();
			$result++;
		}
		
		$compare = $this->comparePublicationDate($startDate, $endDate, $firstPrice->getStartpublicationdate(), $firstPrice->getEndpublicationdate());
		switch ($compare) 
		{
			case 'equal':
				$firstPrice->delete();
				$result++;
				return $result;
				
			case 'part':
				$result--;
				$lastPrice = $firstPrice->getDocumentService()->getNewDocumentInstance();
				$firstPrice->copyPropertiesTo($lastPrice, false);
				$firstPrice->setEndpublicationdate($startDate);
				$lastPrice->setStartpublicationdate($endDate);
				$firstPrice->save();
				$lastPrice->save();
				return $result;
				
			case 'include':
				if ($lastPrice !== null)
				{
					if ($this->comparePublicationDate($startDate, $endDate, $lastPrice->getStartpublicationdate(), $lastPrice->getEndpublicationdate()) === 'endbefore') 
					{
						$lastPrice->setStartpublicationdate($endDate);
						$lastPrice->save();
					}
					else
					{
						$result++;
						$lastPrice->delete();
					}
				}
				$result++;
				$firstPrice->delete();
				return $result;
				
			case 'startafter':
				$firstPrice->setEndpublicationdate($startDate);
				$firstPrice->save();
				if ($lastPrice !== null)
				{
					if ($this->compareDate($startDate, $endDate, $lastPrice->getStartpublicationdate(), $lastPrice->getEndpublicationdate()) === 'endbefore') 
					{
						$lastPrice->setStartpublicationdate($endDate);
						$lastPrice->save();
					}
					else
					{
						$result++;
						$lastPrice->delete();
					}
				}
				return $result;
				
			case 'endbefore':	
				$firstPrice->setStartpublicationdate($endDate);
				$firstPrice->save();
				return $result;
		}
		throw new Exception('Unable to insert price:' . $compare);
	}
	


	
	/**
	 * @param integer $productId
	 * @param integer $shopId
	 * @param integer $targetId
	 * @param integer $thresholdMin
	 * @param integer $priority
	 * @param datetime $forDate
	 */
	public function findPrice($productId, $shopId, $targetId, $thresholdMin, $priority, $forDate)
	{
		$query = $this->createQuery()
			->add(Restrictions::eq('productId', $productId))
			->add(Restrictions::eq('shopId', $shopId))
			->add(Restrictions::eq('targetId', $targetId))
			->add(Restrictions::eq('priority', $priority))
			->add(Restrictions::eq('thresholdMin', $thresholdMin))
			->add(Restrictions::orExp(Restrictions::isNull('endpublicationdate'), Restrictions::gt('endpublicationdate', $forDate)))
			->add(Restrictions::orExp(Restrictions::isNull('startpublicationdate'), Restrictions::le('startpublicationdate', $forDate)));
			
		return 	$query->findUnique();
	}
	
	/**
	 * @param catalog_persistentdocument_price $priceA
	 * @param catalog_persistentdocument_price $priceB
	 * @return priceA equal | part | include | startafter | endbefore | exclude
	 */
	private function compareDate($priceA, $priceB)
	{
		$startA = $priceA->getStartpublicationdate();
		$endA = $priceA->getEndpublicationdate();
		$startB = $priceB->getStartpublicationdate();
		$endB = $priceB->getEndpublicationdate();
		return $this->comparePublicationDate($startA, $endA, $startB, $endB);
	}

	/**
	 * @param datetime $startA
	 * @param datetime $endA
	 * @param datetime $startB
	 * @param datetime $endB
	 * @return priceA equal | part | include | startafter | endbefore | exclude
	 */
	private function comparePublicationDate($startA, $endA, $startB, $endB)
	{
		if ($startA === null) {$startA = "0000-00-00 00:00:00";}
		if ($endA === null) {$endA = "9999-99-99 99:99:99";}
		if ($startB === null) {$startB = "0000-00-00 00:00:00";}
		if ($endB === null) {$endB = "9999-99-99 99:99:99";}
		
		//Framework::info(__METHOD__ . "($startA / $endA) ($startB / $endB)");
		
		if ($startA === $startB && $endA === $endB)
		{
			return 'equal';
		}
		else if ($endA === $endB)
		{
			return ($startB < $startA) ?  'startafter' : 'include';
		}
		else if ($startA === $startB)
		{
			return ($endA < $endB) ? 'endbefore' : 'include';
		}
		else if ($endA <= $startB || $startA >= $endB)
		{
			return 'exclude';
		}
		else if ($startA > $startB && $endA < $endB)
		{
			return 'part';
		}
		else if ($endB > $endA)
		{
			return 'endbefore';
		}
		else if ($startA > $startB)
		{
			return 'startafter';
		}
		return 'include';
		
	}
	// Deprecated.
		
	/**
	 * @deprecated
	 */
	protected function refreshThresholdMax($document)
	{
	}
	
	/**
	 * @deprecated
	 */
	protected function refreshNextThresholdMax($document)
	{
	}
}