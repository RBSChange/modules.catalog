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
			self::$instance = new self();
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
		if ($document->getShopId() === null && intval($parentNodeId) > 0)
		{
			$pDoc = DocumentHelper::getDocumentInstance(intval($parentNodeId));
			if ($pDoc instanceof catalog_persistentdocument_shop)
			{
				$document->setShopId(intval($parentNodeId));
			}
		}
	}

	/**
	 * @param catalog_persistentdocument_tax $document
	 * @param string $forModuleName
	 * @param array $allowedSections
	 * @return array
	 */
//	public function getResume($document, $forModuleName, $allowedSections = null)
//	{
//		$resume = parent::getResume($document, $forModuleName, $allowedSections);
//		return $resume;
//	}

	private $currentTaxeZoneStrategy = null;

	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @param order_CartInfo $cart
	 * @return string | null
	 */
	public function getCurrentTaxZone($shop, $cart = null)
	{
		if ($this->currentTaxeZoneStrategy === null)
		{
			$class = Framework::getConfigurationValue('modules/catalog/currentTaxZoneStrategyClass', false);
			if ($class !== false)
			{
				$this->currentTaxeZoneStrategy = new $class();
			}
			else
			{
				$this->currentTaxeZoneStrategy = false;
			}
		}
		if ($this->currentTaxeZoneStrategy === false)
		{
			return $this->getDefaultShopTaxZone($shop);
		}
		return $this->currentTaxeZoneStrategy->getCurrentTaxZone($shop, $cart);
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return string | null
	 */
	protected function getDefaultShopTaxZone($shop)
	{
		return ($shop) ? $shop->getDefaultTaxZone() : null;;
	}

	/**
	 * 
	 * @param integer $shopId
	 * @param string $taxCategory
	 * @param string $taxZone
	 * @return double | null
	 */
	public function getTaxRate($shopId, $taxCategory, $taxZone)
	{
		$row = $this->createQuery()
			->add(Restrictions::eq('shopId', $shopId))
			->add(Restrictions::eq('taxCategory', $taxCategory))
			->add(Restrictions::eq('taxZone', $taxZone))
			->setProjection(Projections::property('rate'))
			->findUnique();
		return $row ? doubleval($row['rate']) : null;
	}
	
	/**
	 * @param integer $shopId
	 * @param string $taxCategory
	 * @param string $taxZone
	 * @return catalog_persistentdocument_tax | null
	 */
	public function getTaxDocument($shopId, $taxCategory, $taxZone)
	{
		return $this->createQuery()
			->add(Restrictions::eq('shopId', $shopId))
			->add(Restrictions::eq('taxCategory', $taxCategory))
			->add(Restrictions::eq('taxZone', $taxZone))
			->findUnique();
	}
	
	/**
	 * @param string $code
	 * @throws Exception
	 * @return double
	 */
	public function getTaxRateByCode($code)
	{
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
		$rate = $this->getTaxRate($shop->getId(), $taxCategory, $taxZone);
		Framework::info(__METHOD__ . " ($code, $taxCategory, $taxZone) : $rate");
		return $rate;
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return string[]
	 */
	public function getTaxZonesForShop($shop)
	{
		return $this->createQuery()
			->add(Restrictions::eq('shopId', $shop->getId()))
			->setProjection(Projections::groupProperty('taxZone', 'taxZone'))
			->findColumn('taxZone');
	}
	
	/**
	 * @param catalog_persistentdocument_shop $taxZone
	 * @return zone_persistentdocument_zone[]
	 */
	public function getZonesForTaxZone($taxZone)
	{
		return zone_ZoneService::getInstance()->getZonesByCode($taxZone);
	}	
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return zone_persistentdocument_zone[]
	 */
	public function getZonesForShop($shop)
	{
		$taxZones = $this->getTaxZonesForShop($shop);	
		$result = array();
		foreach ($taxZones as $taxZone) 
		{
			$result = array_merge($result, $this->getZonesForTaxZone($taxZone));
		}
		return $result;
	}

	/**
	 * @param integer $shopId
	 * @param string $taxZone
	 * @return catalog_persistentdocument_tax
	 */
	public function addNoTaxForShopAndZone($shopId, $taxZone)
	{
		$tax = $this->getTaxDocument($shopId, '0' , $taxZone);
		if ($tax === null)
		{
			$tax = $this->getNewDocumentInstance();
			$tax->setLabel('-');
			$tax->setShopId($shopId);
			$tax->setTaxCategory('0');
			$tax->setTaxZone($taxZone);
			$tax->setRate(0.0);
			$this->save($tax);
		}
		return $tax;
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return catalog_persistentdocument_tax[]
	 */
	public function getByShop($shop)
	{
		return $this->createQuery()
			->add(Restrictions::eq('shopId', $shop->getId()))
			->find();
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return array[taxCategory => [label =>, rate =>]
	 */
	public function getBoTaxeInfoForShop($shop)
	{
		$taxZone = $shop->getBoTaxZone() ? $shop->getBoTaxZone() : $shop->getDefaultTaxZone();
		$rows = $this->createQuery()
				->add(Restrictions::eq('shopId', $shop->getId()))
				->add(Restrictions::eq('taxZone', $taxZone))
				->setProjection(Projections::property('rate'), Projections::property('label'), Projections::property('taxCategory'))
				->addOrder(Order::asc('rate'))
				->find();
		$result = array();
		$hasNoTaxCategory = false;
		
		foreach ($rows as $row) 
		{
			if ($row['taxCategory'] === '0') {$hasNoTaxCategory = true;}
			$result[$row['taxCategory']] = array('rate' => doubleval($row['rate']), 'label' => $row['label']);
		}
		
		if (!$hasNoTaxCategory)
		{
			$tax = $this->addNoTaxForShopAndZone($shop->getId(), $taxZone);
			$result = array_merge(array($tax->getTaxCategory() => array('rate' => $tax->getRate(), 'label' => $tax->getLabel())), $result);
		}
		
		return $result;
	}
}