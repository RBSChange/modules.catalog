<?php
/**
 * catalog_BillingareaService
 * @package modules.catalog
 */
class catalog_BillingareaService extends f_persistentdocument_DocumentService
{
	/**
	 * @var catalog_BillingareaService
	 */
	private static $instance;

	/**
	 * @return catalog_BillingareaService
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
	 * @return catalog_persistentdocument_billingarea
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_catalog/billingarea');
	}

	/**
	 * Create a query based on 'modules_catalog/billingarea' model.
	 * Return document that are instance of modules_catalog/billingarea,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_catalog/billingarea');
	}
	
	/**
	 * Create a query based on 'modules_catalog/billingarea' model.
	 * Only documents that are strictly instance of modules_catalog/billingarea
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_catalog/billingarea', false);
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @param catalog_persistentdocument_shop $shop
	 * @return catalog_persistentdocument_billingarea
	 * @throws Exception
	 */
	public function getCurrentBillingAreaForShop($shop, $refresh = false)
	{
		if ($shop instanceof catalog_persistentdocument_shop)
		{
			$data = Controller::getInstance()->getContext()->getStorage()->read('CurrentBillingAreaForShop');
			if (!$refresh && is_array($data) && isset($data[$shop->getId()]))
			{
				$billingArea = catalog_persistentdocument_billingarea::getInstanceById($data[$shop->getId()]);
			}
			else
			{
				$billingArea = null;
				$class = Framework::getConfigurationValue('modules/catalog/currentBillingAreaStrategyClass', false);
				if ($class !== false && f_util_ClassUtils::classExists($class))
				{
					$strategy = new $class();
					$billingArea = $strategy->getCurrentBillingArea($shop);					
					
				}
				
				if (!($billingArea instanceof catalog_persistentdocument_billingarea))
				{
					$billingArea = $shop->getDefaultBillingArea();
				}
								
				if (is_array($data))
				{
					$data[$shop->getId()] = $billingArea->getId();
				}
				else
				{
					$data = array($shop->getId() => $billingArea->getId());
				}
				Controller::getInstance()->getContext()->getStorage()->write('CurrentBillingAreaForShop', $data);
			}			
			return $billingArea;
		}
		throw new Exception('No shop defined');
	}
	
	/**
	 * @param catalog_persistentdocument_billingarea $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function preUpdate($document, $parentNodeId)
	{
		if ($document->getNewTaxLabel())
		{
			$tax = catalog_TaxService::getInstance()->getTaxDocumentByKey($document->getId(), $document->getNewTaxCategory(), $document->getNewTaxZone());
			if ($tax === null)
			{
				$tax = catalog_TaxService::getInstance()->getNewDocumentInstance();
				$tax->setBillingAreaId($document->getId());
				$tax->setTaxCategory($document->getNewTaxCategory());
				$tax->setTaxZone($document->getNewTaxZone());
			}
			$tax->setLabel($document->getNewTaxLabel());
			$tax->setRate($document->getNewTaxRate());
			$document->resetNewTaxInfos();
			$tax->save();
			
			if ($tax->getTaxCategory() != '0')
			{
				catalog_TaxService::getInstance()->addNoTaxForBillingAreaAndZone($document->getId(), $tax->getTaxZone());
			}
		}
		
		if ($document->isPropertyModified('defaultZone'))
		{
			catalog_TaxService::getInstance()->addNoTaxForBillingAreaAndZone($document->getId(), $document->getDefaultZone());
		}
	}
	
	/**
	 * @param catalog_persistentdocument_billingarea $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function postInsert($document, $parentNodeId)
	{
		catalog_TaxService::getInstance()->addNoTaxForBillingAreaAndZone($document->getId(), $document->getDefaultZone());
	}
		
	/**
	 * @param catalog_persistentdocument_billingarea $document
	 * @return void
	 */
	protected function preDelete($document)
	{
		$taxArray = catalog_TaxService::getInstance()->getByBillingArea($document);
		foreach ($taxArray as $tax)
		{
			/* @var $tax catalog_persistentdocument_tax */
			$tax->delete();
		}
	}
		
	/**
	 * @param double $valueWithoutTax
	 * @param catalog_persistentdocument_shop $shop
	 * @param string $taxCategory
	 * @return array
	 */
	public function buildBoPriceEditInfos($valueWithoutTax, $shop, $taxCategory)
	{
		$billingArea = $shop->getDefaultBillingArea();
		
		$currency = $billingArea->getCurrency();
		$editTTC = $billingArea->getBoEditWithTax();
		
		$ts = catalog_TaxService::getInstance();
		$taxCategories = $ts->getBoTaxeInfoForBillingArea($billingArea);
		$taxRate = $taxCategories[$taxCategory]['rate'];
		$pf = catalog_PriceFormatter::getInstance();

		$valueWithTax = $pf->round($ts->addTaxByRate($valueWithoutTax, $taxRate), $currency->getCode());
		$valueWithoutTax = $pf->round($valueWithoutTax, $currency->getCode());
		
		$array = array('value' => $editTTC ? $valueWithTax : $valueWithoutTax, 'editTTC' => $editTTC, 
			'valueTTC' => $valueWithTax, 'valueHT' => $valueWithoutTax, 
			'taxCategory' => $taxCategory, 'taxCategories' => $taxCategories, 
			'currency' => $currency->getSymbol(), 'currencyCode' => $currency->getCode());
		return $array;
	}
	
	/**
	 * @param string $value
	 * @param catalog_persistentdocument_shop $shop
	 * @return list($valueWithoutTax, $taxCategory)
	 */
	public function parseBoPriceEditInfos($value, $shop)
	{
		$parts = explode(',', strval($value));
		if (count($parts) != 2 || $parts[0] == '' || $parts[1] == '')
		{
			$parts = array(0.0, '0');
		}
		$taxCategory = $parts[1];
		$valueWithoutTax = doubleval($parts[0]);
		if ($valueWithoutTax > 0)
		{
			$billingArea = $shop->getDefaultBillingArea();
			$editTTC = $billingArea->getBoEditWithTax();	
			if ($editTTC)
			{
				$ts = catalog_TaxService::getInstance();
				$taxRate = $ts->getTaxRateByKey($billingArea->getId(), $taxCategory, $billingArea->getDefaultZone());
				$valueWithoutTax = $ts->removeTaxByRate($valueWithoutTax, $taxRate);
			}
		}
		return array($valueWithoutTax, $taxCategory);
	}

}