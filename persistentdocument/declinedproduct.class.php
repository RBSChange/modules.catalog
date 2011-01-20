<?php
/**
 * catalog_persistentdocument_declinedproduct
 * @package modules.catalog
 */
class catalog_persistentdocument_declinedproduct extends catalog_persistentdocument_declinedproductbase
{	
	
	/**
	 * @param string $actionType
	 * @param array $formProperties
	 */
	public function addFormProperties($propertiesNames, &$formProperties)
	{
		$preferences = ModuleService::getInstance()->getPreferencesDocument('catalog');
		$formProperties['suggestComplementaryFeederClass'] = $preferences->getSuggestComplementaryFeederClass(); 		
		$formProperties['suggestSimilarFeederClass'] = $preferences->getSuggestSimilarFeederClass(); 		
		$formProperties['suggestUpsellFeederClass'] = $preferences->getSuggestUpsellFeederClass(); 		
	}
	
	/**
	 * @see f_persistentdocument_PersistentDocumentImpl::addTreeAttributes()
	 * @param string $moduleName
	 * @param string $treeType
	 * @param unknown_type $nodeAttributes
	 */
	protected function addTreeAttributes($moduleName, $treeType, &$nodeAttributes)
	{
		$nodeAttributes['block'] = 'modules_catalog_product'; 
		if ($treeType == 'wlist')
		{
			$detailVisual = $this->getDefaultVisual();
			if ($detailVisual)
			{
				$nodeAttributes['thumbnailsrc'] = MediaHelper::getPublicFormatedUrl($detailVisual, "modules.uixul.backoffice/thumbnaillistitem");			
			}
		}
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return media_persistentdocument_media
	 */
	public function getDefaultVisual($shop = null)
	{
		return $this->getDocumentService()->getDefaultVisual($this, $shop);
	}
	
	
	/**
	 * @return catalog_persistentdocument_productdeclination[]
	 */
	public function getDeclinationArray()
	{
		return catalog_ProductdeclinationService::getInstance()->getArrayByDeclinedProduct($this);
	}
	
	/**
	 * @return catalog_persistentdocument_productdeclination[]
	 */
	public function getPublishedDeclinationArray()
	{
		return catalog_ProductdeclinationService::getInstance()->getPublishedArrayByDeclinedProduct($this);
	}	
	
	/**
	 * @return catalog_persistentdocument_productdeclination
	 */
	public function getFirstDeclination()
	{
		return catalog_ProductdeclinationService::getInstance()->getFirstByDeclinedProduct($this);
	}
	
	/**
	 * @return boolean
	 */
	public function isPricePanelEnabled()
	{
		return $this->getSynchronizePrices();
	}

	/**
     * integer
	 */
	private $synchronizePricesFrom;
	
	/**
	 * @return integer
	 */
	public function getSynchronizePricesFrom()
	{
		return $this->synchronizePricesFrom;
	}
	
	/**
	 * @param integer $declinationId
	 */
	public function setSynchronizePricesFrom($declinationId)
	{
		$declinationId = intval($declinationId);
		Framework::info(__METHOD__ . "($declinationId)");
		if ($declinationId > 0)
		{
			$this->synchronizePricesFrom = $declinationId;
			$this->setSynchronizePrices(true);
		}
		else
		{
			$this->synchronizePricesFrom = null;
			$this->setSynchronizePrices(false);
		}
	}
	
	/**
	 * @param integer $declinationId
	 */
	public function clearSynchronizePricesFrom()
	{
		$this->synchronizePricesFrom = null;
	}
	
	/**
	 * @return string
	 */
	public function getPricePanelDisabledMessage()
	{
		if (!$this->getSynchronizePrices())
		{
			return LocaleService::getInstance()->transFO('m.catalog.bo.doceditor.panel.prices.disabled.declinedproduct-not-synchronized', array('ucf', 'html'));
		}
		return null;
	}
	
	/**
	 * @param Integer $shopId
	 * @return Boolean
	 */
	public function isInShop($shopId)
	{
		return $this->getDocumentService()->hasDeclinationInShopId($this, $shopId);
	}
	
	/**
	 * @return catalog_ProductAxe[]
	 */
	public function getAxes()
	{
		return $this->getDocumentService()->getAxes($this);
	}
	
	/**
	 * @return catalog_ProductAxe[]
	 */
	public function getAxesInList()
	{
		return $this->getDocumentService()->getAxesInList($this);
	}
	
	//function for Back office Editor
	
	/**
	 * @var array
	 */
	private $declination0;
	
	/**
	 * @return array ['label', 'visual', 'codeReference', 'stockQuantity']
	 */
	public function getDeclination0Infos()
	{
		return ($this->declination0 !== null) ? $this->declination0 : array();
	}
	
	/**
	 * @param string $value
	 */
	public function setDeclinationLabel0($value)
	{
		if ($this->declination0 === null) {$this->declination0 = array();}
		$this->declination0['label'] = $value;
	}
		
	/**
	 * @param media_persistentdocument_media $value
	 */
	public function setDeclinationVisual0($value)
	{
		if ($this->declination0 === null) {$this->declination0 = array();}
		$this->declination0['visual'] = $value;
	}

	/**
	 * @param string $value
	 */
	public function setDeclinationCodeReference0($value)
	{
		if ($this->declination0 === null) {$this->declination0 = array();}
		$this->declination0['codeReference'] = $value;
	}
	
	/**
	 * @param integer $value
	 */
	public function setDeclinationStockQuantity0($value)
	{
		if ($this->declination0 === null) {$this->declination0 = array();}
		$this->declination0['stockQuantity'] = $value;
	}
	
	
	/**
	 * @return array
	 */
	public final function getAttributes()
	{
		$val = $this->getSerializedattributes();
		if (f_util_StringUtils::isEmpty($val))
		{
			return array();
		}
		return unserialize($val);
	}
	
	/**
	 * @param array $attributes
	 */
	private function setAttributes($attributes)
	{
		if (f_util_ArrayUtils::isEmpty($attributes))
		{
			$this->setSerializedattributes(null);
		}
		else
		{
			$this->setSerializedattributes(serialize($attributes));
		}
	}

	/**
	 * Return JSON String
	 * @return string
	 */
	public function getAttributesJSON()
	{
		$attrDef = catalog_AttributefolderService::getInstance()->getAttributesForProduct($this);
		if (count($attrDef) == 0)
		{
			return JsonService::getInstance()->encode(array('attrDef' => null, 'attrVal' => null));
		}
		$attrValOri = $this->getAttributes();
		$attrVal = array();
		foreach ($attrDef as $def) 
		{
			$code = $def['code'];
			if (isset($attrValOri[$code]))
			{
				$attrVal[$code] = $attrValOri[$code];
			}
		}
		if (count($attrVal) == 0)
		{
			$attrVal = null;
		}
		return JsonService::getInstance()->encode(array('attrDef' => $attrDef, 'attrVal' => $attrVal));
	}
		
	/**
	 * @param string $json
	 */
	public function setAttributesJSON($json)
	{
		if (f_util_StringUtils::isEmpty($json))
		{
			$this->setSerializedattributes(null);
		}
		else
		{
			$attributes = JsonService::getInstance()->decode($json);
			$this->setAttributes($attributes);
		}
	}
	
	//Front office templating
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return catalog_persistentdocument_productdeclination[]
	 */
	public function getPublishedDeclinationsInShop($shop = null)
	{
		if ($shop === null)
		{
			$shop = catalog_ShopService::getInstance()->getCurrentShop();
			if ($shop === null) {return  array();}
		}
		return $this->getDocumentService()->getPublishedDeclinationsInShop($this, $shop);
	}

	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return catalog_persistentdocument_productdeclination
	 */
	public function getPublishedDefaultDeclinationInShop($shop = null)
	{
		if ($shop === null)
		{
			$shop = catalog_ShopService::getInstance()->getCurrentShop();
			if ($shop === null) {return  array();}
		}
		return $this->getDocumentService()->getPublishedDefaultDeclinationInShop($this, $shop);
	}	
	
}