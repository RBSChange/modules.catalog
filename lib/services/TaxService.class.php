<?php
/**
 * catalog_TaxService
 * @package modules.catalog
 */
class catalog_TaxService extends f_persistentdocument_DocumentService
{
	/**
	 * @var catalog_TaxService
	 */
	private static $instance;

	/**
	 * @return catalog_TaxService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}

	/**
	 * @return catalog_persistentdocument_tax
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_catalog/tax');
	}

	/**
	 * Create a query based on 'modules_catalog/tax' model.
	 * Return document that are instance of modules_catalog/tax,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_catalog/tax');
	}
	
	/**
	 * Create a query based on 'modules_catalog/tax' model.
	 * Only documents that are strictly instance of modules_catalog/tax
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_catalog/tax', false);
	}
	
	/**
	 * @param catalog_persistentdocument_tax $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
	protected function preSave($document, $parentNodeId)
	{
		$document->setInsertInTree(false);
		if ($document->getBillingAreaId() === null && intval($parentNodeId) > 0)
		{
			$pDoc = DocumentHelper::getDocumentInstance(intval($parentNodeId));
			if ($pDoc instanceof catalog_persistentdocument_billingarea)
			{
				$document->setBillingAreaId(intval($parentNodeId));
			}
		}
	}
	
	/**
	 * @param integer $billingAreaId
	 * @return catalog_persistentdocument_tax[]
	 */
	public function getByBillingAreaId($billingAreaId)
	{
		return $this->createQuery()->add(Restrictions::eq('billingAreaId', $billingAreaId))->find();
	}
	
	/**
	 * @param catalog_persistentdocument_billingarea $billingArea
	 * @return catalog_persistentdocument_tax[]
	 */
	public function getByBillingArea($billingArea)
	{
		if ($billingArea instanceof catalog_persistentdocument_billingarea)
		{
			return $this->getByBillingAreaId($billingArea->getId());
		}
		return array();
	}
	
	
	/**
	 * @param integer $billingAreaId
	 * @param string $taxCategory
	 * @param string $taxZone
	 * @return catalog_persistentdocument_tax | null
	 */
	public function getTaxDocumentByKey($billingAreaId, $taxCategory, $taxZone)
	{
		return $this->createQuery()
		->add(Restrictions::eq('billingAreaId', $billingAreaId))
		->add(Restrictions::eq('taxCategory', $taxCategory))
		->add(Restrictions::eq('taxZone', $taxZone))
		->findUnique();
	}
	
	
	/**
	 * @param catalog_persistentdocument_billingarea $billingArea
	 * @return array[taxCategory => [label =>, rate =>]
	 */
	public function getBoTaxeInfoForBillingArea($billingArea)
	{
		$taxZone = $billingArea->getDefaultZone();		
		$rows = $this->createQuery()
			->add(Restrictions::eq('billingAreaId', $billingArea->getId()))
			->add(Restrictions::eq('taxZone', $taxZone))
			->setProjection(Projections::property('rate'), Projections::property('label'), Projections::property('taxCategory'))
			->addOrder(Order::asc('rate'))
			->find();
		$result = array();
		$hasNoTaxCategory = false;
	
		foreach ($rows as $row)
		{
			if ($row['taxCategory'] === '0') 
			{
				$hasNoTaxCategory = true;
			}
			$result[$row['taxCategory']] = array('rate' => doubleval($row['rate']), 'label' => $row['label']);
		}
	
		if (!$hasNoTaxCategory)
		{
			$tax = $this->addNoTaxForBillingAreaAndZone($billingArea->getId(), $taxZone);
			$result = array_merge(array($tax->getTaxCategory() => array('rate' => $tax->getRate(), 'label' => $tax->getLabel())), $result);
		}
	
		return $result;
	}
	
	/**
	 * @param catalog_persistentdocument_billingarea $billingArea
	 * @return string[]
	 */
	public function getZonesCodeForBillingArea($billingArea)
	{
		return $this->createQuery()
		->add(Restrictions::eq('billingAreaId', $billingArea->getId()))
		->setProjection(Projections::groupProperty('taxZone', 'taxZone'))
		->findColumn('taxZone');
	}
	
	/**
	 * @param catalog_persistentdocument_billingarea $billingArea
	 * @return zone_persistentdocument_zone[]
	 */
	public function getAllZonesForBillingArea($billingArea)
	{
		$taxZones = $this->getZonesCodeForBillingArea($billingArea);
		$result = array();
		foreach ($taxZones as $taxZone)
		{
			$result = array_merge($result, $this->getZonesForTaxZone($taxZone));
		}
		return $result;
	}
	
	
	/**
	 * @param string $taxZone
	 * @return zone_persistentdocument_zone[]
	 */
	public function getZonesForTaxZone($taxZone)
	{
		return zone_ZoneService::getInstance()->getZonesByCode($taxZone);
	}
	
	
	/**
	 * @param integer $billingAreaId
	 * @param string $taxZone
	 * @return catalog_persistentdocument_tax
	 */
	public function addNoTaxForBillingAreaAndZone($billingAreaId, $taxZone)
	{
		$tax = $this->getTaxDocumentByKey($billingAreaId, '0' , $taxZone);
		if ($tax === null)
		{
			$tax = $this->getNewDocumentInstance();
			$tax->setLabel('-');
			$tax->setBillingAreaId($billingAreaId);
			$tax->setTaxCategory('0');
			$tax->setTaxZone($taxZone);
			$tax->setRate(0.0);
			$this->save($tax);
		}
		return $tax;
	}
	
	/**
	 * @param integer $billingAreaId
	 * @param string $taxCategory
	 * @param string $taxZone
	 * @return float | null
	 */
	public function getTaxRateByKey($billingAreaId, $taxCategory, $taxZone)
	{
		$row = $this->createQuery()
		->add(Restrictions::eq('billingAreaId', $billingAreaId))
		->add(Restrictions::eq('taxCategory', $taxCategory))
		->add(Restrictions::eq('taxZone', $taxZone))
		->setProjection(Projections::property('rate'))
		->findUnique();
		return $row ? doubleval($row['rate']) : null;
	}	
	
	
	/**
	 * @param float $valueWithTax
	 * @param float $valueWithoutTax
	 * @return float
	 */
	public function getTaxRateByValue($valueWithTax, $valueWithoutTax)
	{
		if ($valueWithoutTax > 0)
		{
			return ($valueWithTax / $valueWithoutTax) - 1;
		}
		return 0;
	}	
	
	/**
	 * @param float $valueWithoutTax
	 * @param float $taxRate
	 * @return float
	 */
	public function addTaxByRate($valueWithoutTax, $taxRate)
	{
		return ($valueWithoutTax * (1 + $taxRate));
	}	
	
	/**
	 * @param float $valueWithTax
	 * @param float $taxRate
	 * @return float
	 */
	public function removeTaxByRate($valueWithTax, $taxRate)
	{
		return ($valueWithTax / (1 + $taxRate));
	}
	
	/**
	 * @param float $taxRate
	 * @return string
	 */
	public function formatRate($taxRate)
	{
		return (round($taxRate * 100, 2)) . "%";
	}
	
	/**
	 * @param string $formatedTaxRate
	 * @return float
	 */
	public function parseRate($formatedTaxRate)
	{
		return floatval($formatedTaxRate) / 100;
	}
	
	private $currentTaxeZone = array();

	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @param order_CartInfo $cart
	 * @param boolean $refresh
	 * @return string | null
	 */
	public function getCurrentTaxZone($shop, $cart = null, $refresh = false)
	{	
		if ($refresh) {$this->currentTaxeZone = array();}
		if (!isset($this->currentTaxeZone[$shop->getId()]))
		{
			Framework::fatal(f_util_ProcessUtils::getBackTrace());
			$class = Framework::getConfigurationValue('modules/catalog/currentTaxZoneStrategyClass', false);
			if ($class !== false)
			{
				$currentTaxeZoneStrategy = new $class();
				$this->currentTaxeZone[$shop->getId()] = $currentTaxeZoneStrategy->getCurrentTaxZone($shop, $cart);
			}
			else
			{
				$this->currentTaxeZone[$shop->getId()] = $this->getDefaultShopTaxZone($shop, $cart);
			}
		}
		return $this->currentTaxeZone[$shop->getId()];
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @param string | null $zone
	 */
	public function setCurrentTaxZone($shop, $zone)
	{
		if ($shop instanceof catalog_persistentdocument_shop)
		{
			$this->currentTaxeZone[$shop->getId()] = $zone;
		}
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @param order_CartInfo $cart
	 * @return string | null
	 */
	protected function getDefaultShopTaxZone($shop, $cart = null)
	{
		return ($shop) ? $shop->getCurrentBillingArea()->getDefaultZone() : null;;
	}
	
	
	//DEPRECATED
	
	/**
	 * @deprecated use getTaxRateByKey
	 */
	public function getTaxRate($shopId, $taxCategory, $taxZone)
	{
		if (Framework::inDevelopmentMode())
		{
			Framework::fatal(f_util_ProcessUtils::getBackTrace());
		}
		else
		{
			Framework::warn('DEPRECATED Call to: ' . __METHOD__);
		}
		$shop = DocumentHelper::getDocumentInstanceIfExists($shopId);
		if ($shop instanceof catalog_persistentdocument_shop)
		{
			$billingAreaId = $shop->getDefaultBillingArea()->getId();
			return $this->getTaxRateByKey($billingAreaId, $taxCategory, $taxZone);
		}
		return  null;
	}
	
	/**
	 * @deprecated use getTaxDocumentByKey
	 */
	public function getTaxDocument($shopId, $taxCategory, $taxZone)
	{
		if (Framework::inDevelopmentMode())
		{
			Framework::fatal(f_util_ProcessUtils::getBackTrace());
		}
		else
		{
			Framework::warn('DEPRECATED Call to: ' . __METHOD__);
		}
		$shop = DocumentHelper::getDocumentInstanceIfExists($shopId);
		if ($shop instanceof catalog_persistentdocument_shop)
		{
			$billingAreaId = $shop->getDefaultBillingArea()->getId();
			return $this->getTaxDocumentByKey($billingAreaId, $taxCategory, $taxZone);
		}
		return null;
	}
	
	/**
	 * @deprecated
	 */
	public function getTaxRateByCode($code)
	{
		if (Framework::inDevelopmentMode())
		{
			Framework::fatal(f_util_ProcessUtils::getBackTrace());
		}
		else
		{
			Framework::warn('DEPRECATED Call to: ' . __METHOD__);
		}
		$parts = explode(',', $code);
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		if ($shop === null) {throw new Exception('No current shop defined');}
		if (count($parts) == 2)
		{
			list($taxZone, $taxCategory) = $parts;
		}
		else
		{
			$taxCategory = $code;
			$taxZone = $this->getCurrentTaxZone($shop);
		}
		$rate = $this->getTaxRateByKey($shop->getCurrentBillingArea()->getId(), $taxCategory, $taxZone);
		return $rate;
	}
	
	/**
	 * @deprecated use getZonesCodeForBillingArea
	 */
	public function getTaxZonesForShop($shop)
	{
		if (Framework::inDevelopmentMode())
		{
			Framework::fatal(f_util_ProcessUtils::getBackTrace());
		}
		else
		{
			Framework::warn('DEPRECATED Call to: ' . __METHOD__);
		}
		return $this->getZonesCodeForBillingArea($shop->getDefaultBillingArea());
	}
	

	/**
	 * @deprecated use getAllZonesForBillingArea
	 */
	public function getZonesForShop($shop)
	{
		if (Framework::inDevelopmentMode())
		{
			Framework::fatal(f_util_ProcessUtils::getBackTrace());
		}
		else
		{
			Framework::warn('DEPRECATED Call to: ' . __METHOD__);
		}
		return $this->getAllZonesForBillingArea($shop->getDefaultBillingArea());
	}

	/**
	 * @deprecated use addNoTaxForBillingAreaAndZone
	 */
	public function addNoTaxForShopAndZone($shopId, $taxZone)
	{
		if (Framework::inDevelopmentMode())
		{
			Framework::fatal(f_util_ProcessUtils::getBackTrace());
		}
		else
		{
			Framework::warn('DEPRECATED Call to: ' . __METHOD__);
		}
		$shop = DocumentHelper::getDocumentInstanceIfExists($shopId);
		if ($shop instanceof catalog_persistentdocument_shop)
		{
			$billingAreaId = $shop->getDefaultBillingArea()->getId();
			return $this->addNoTaxForBillingAreaAndZone($billingAreaId, $taxZone);
		}
	}
	
	/**
	 * @deprecated use getBoTaxeInfoForBillingArea
	 */
	public function getBoTaxeInfoForShop($shop)
	{
		if (Framework::inDevelopmentMode())
		{
			Framework::fatal(f_util_ProcessUtils::getBackTrace());
		}
		else
		{
			Framework::warn('DEPRECATED Call to: ' . __METHOD__);
		}
		return $this->getBoTaxeInfoForBillingArea($shop->getDefaultBillingArea());
	}
		
	/**
	 * @deprecated
	 */
	public function getByShop($shop)
	{
		if (Framework::inDevelopmentMode())
		{
			Framework::fatal(f_util_ProcessUtils::getBackTrace());
		}
		else
		{
			Framework::warn('DEPRECATED Call to: ' . __METHOD__);
		}
		return $this->getByBillingArea($shop->getDefaultBillingArea());
	}
}