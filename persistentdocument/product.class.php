<?php
/**
 * catalog_persistentdocument_product
 * @package modules.catalog
 */
class catalog_persistentdocument_product extends catalog_persistentdocument_productbase implements rss_Item 
{
	/**
	 * Get the indexable document
	 * @param catalog_persistentdocument_shop $shop
	 * @return indexer_IndexedDocument
	 */
	public function getIndexedDocumentForShop($shop)
	{
		$website = $shop->getWebsite();
		$primaryShelf = $this->getPrimaryShelf($website);
		$topic = catalog_ShelfService::getInstance()->getRelatedTopicByShop($primaryShelf, $shop);
		
		$indexedDoc = new indexer_IndexedDocument();
		$indexedDoc->setId($this->getId());
		$indexedDoc->setDocumentModel($this->getDocumentModelName());
		$indexedDoc->setStringField('documentFamily', 'products');
		$indexedDoc->setLabel($this->getLabel());
		$indexedDoc->setLang(RequestContext::getInstance()->getLang());
		$indexedDoc->setText(
			f_util_StringUtils::htmlToText($this->getDescription())
			. ' ' . $this->getBrandLabel()
			. ' ' . $primaryShelf->getLabel()
			. ' ' . $this->getPrimaryTopShelf($website)->getLabel()
		);
		$indexedDoc->setParentWebsiteId($website->getId());
		$indexedDoc->setParentTopicId($topic->getId());
		
		return $indexedDoc;
	}
	
	/**
	 * Returns full product name in form of "{product} by {brand}".
	 * @return string full name
	 */
	public function getFullName()
	{
		$brandLabel = $this->getBrandLabel();
		if ($brandLabel)
		{
			$replacements = array('product' => $this->getLabel(), 'brand' => $brandLabel);
			$fullName = f_Locale::translate('&modules.catalog.frontoffice.fullProductName;', $replacements);
		}
		else
		{
			$fullName = $this->getLabel();
		}

		return $fullName;
	}
	
	/**
	 * @return String
	 */
	public function getBrandLabel()
	{
		$brand = $this->getBrand();
		if ($brand !== null)
		{
			return $brand->getLabel();
		}
		return null;
	}
	
	/**
	 * @return String
	 */
	public function getBrandLabelAsHtml()
	{
		$brand = $this->getBrand();
		if ($brand !== null)
		{
			return $brand->getLabelAsHtml();
		}
		return null;
	}

	/**
	 * @return String
	 */
	public function getShortDescription($maxCount = 80)
	{
		$desc = str_replace(array('&', '<', '>', '\'', '"'), array('&amp;', '&lt;', '&gt;', '&#39;', '&quot;'), f_util_StringUtils::htmlToText($this->getDescription(), false));
		return f_util_StringUtils::shortenString($desc, $maxCount);
	}

	/**
	 * @return String
	 */
	public function getLinkTitle()
	{
		return f_util_HtmlUtils::textToHtml(f_Locale::translate('&modules.catalog.frontoffice.Titlelinkdetailproduct;', array('name' => $this->getLabel())));
	}

	/**
	 * @param website_pesistentdocument_website $website
	 * @return catalog_persistentdocument_shelf
	 */
	public function getPrimaryShelf($website = null)
	{
		return $this->getDocumentService()->getPrimaryShelf($this, $website);
	}
	
	/**
	 * @param website_pesistentdocument_website $website
	 * @return catalog_persistentdocument_shelf
	 */
	public function getPrimaryTopShelf($website = null)
	{
		$shelf = $this->getDocumentService()->getPrimaryShelf($this, $website);
		return catalog_ShelfService::getInstance()->getTopShelfByShelf($shelf);
	}
	
	/**
	 * @return String
	 */
	public function getPathForUrl()
	{
		return catalog_ReferencingService::getInstance()->getPathForUrlByProduct($this);
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
	 * @return media_persistentdocument_media[]
	 */
	public function getAllVisuals($shop)
	{
		return array_unique(array_merge(array($this->getDefaultVisual($shop)), $this->getAdditionnalVisualArray()));
	}
	
	/**
	 * @return media_persistentdocument_media[]
	 */
	public function getAdditionnalVisualArray()
	{
		return array();
	}
	
	/**
	 * @return String
	 */
	public function getFormattedRatingAverage()
	{
		$website = website_WebsiteModuleService::getInstance()->getCurrentWebsite();
		$ratingAverage = $this->getDocumentService()->getRatingAverage($this, $website->getId());
		if ($ratingAverage === null)
		{
			return f_Locale::translate('&modules.catalog.frontoffice.No-rating-yet;');
		}
		else
		{
			return number_format($ratingAverage, 1);
		}
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @param customer_persistentdocument_customer $customer nullable
	 * @param Double $quantity
	 * @return catalog_persistentdocument_price
	 */
	public function getPrice($shop, $customer, $quantity = 1)
	{
		$targetIds = catalog_PriceService::getInstance()->convertCustomerToTargetIds($customer);
		return $this->getDocumentService()->getPriceByTargetIds($this, $shop, $targetIds, $quantity);
	}
	
	/**
	 * @return String
	 */
	public function getFormattedCurrentShopPrice()
	{
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		$price = $this->getPrice($shop, null);
		if ($price !== null)
		{
			return $price->getFormattedValueWithTax();
		}
		return null;
	}
	
	/**
	 * @return String
	 */
	public function getPriceForCurrentShopAndCustomer()
	{
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		$curtomer = customer_CustomerService::getInstance()->getCurrentCustomer();
		return $this->getPrice($shop, $curtomer);
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @param customer_persistentdocument_customer $customer
	 * @return catalog_persistentdocument_price[]
	 */
	public function getPrices($shop, $customer)
	{
		$targetIds = catalog_PriceService::getInstance()->convertCustomerToTargetIds($customer);
		return $this->getDocumentService()->getPricesByTargetIds($this, $shop, $targetIds);
	}
	
	/**
	 * @param Integer $shopId
	 * @return Boolean
	 */
	public function isInShop($shopId)
	{
		return catalog_CompiledproductService::getInstance()->createQuery()
			->add(Restrictions::eq('product', $this))	
			->add(Restrictions::eq('shopId', $shopId))
			->setProjection(Projections::rowCount('count'))
			->setFetchMode('count')
			->findUnique() > 0;
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return Boolean
	 */
	public function isAvailable($shop)
	{
		if ($this instanceof catalog_StockableDocument)
		{
			return catalog_StockService::getInstance()->isAvailable($this);
		}
		return true;
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return String
	 */
	public function getAvailability($shop = null)
	{
		if ($this instanceof catalog_StockableDocument)
		{
			return catalog_AvailabilityStrategy::getStrategy()->getAvailability($this->getStockLevel());
		}
		return null;
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return Boolean
	 */
	public function canBeDisplayed($shop)
	{
		return $shop->getDisplayOutOfStock() || $this->isAvailable($shop);
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return Boolean
	 */
	public function canBeOrdered($shop)
	{
		$cms = catalog_ModuleService::getInstance();
		return $cms->isCartEnabled() && ($shop->getAllowOrderOutOfStock() || $this->isAvailable($shop));
	}	
	
	/**
	 * @return boolean
	 */
	public function isCompilable()
	{
		return true;
	}
	
	/**
	 * @return catalog_persistentdocument_product
	 */
	public function getProductToCompile()
	{
		return $this;
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @param String $type from list 'modules_catalog/crosssellingtypes'
	 * @param String $sortBy from list 'modules_catalog/crosssellingsortby'
	 * @return catalog_persistentdocument_product[]
	 */
	public function getDisplayableCrossSelling($shop, $type = 'complementary', $sortBy = 'fieldorder')
	{
		return $this->getDocumentService()->getDisplayableCrossSelling($this, $shop, $type, $sortBy);
	}
	
	/**
	 * @return String
	 */
	public function getDetailBlockName()
	{
		throw new Exception('getDetailBlockName() must be redefined in each product sub-classes!');
	}
	
	/**
	 * @return String
	 */
	public function getDetailBlockModule()
	{
		return 'catalog';
	}
	
	//BACK OFFICE FORM PROPERTIES
	
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
	
	
	/**
	 * @see rss_Item::getRSSDate()
	 *
	 * @return String
	 */
	public function getRSSDate()
	{
		return $this->getModificationdate();
	}
	
	/**
	 * @see rss_Item::getRSSDescription()
	 *
	 * @return String
	 */
	public function getRSSDescription()
	{
		$template = TemplateLoader::getInstance()
			->setMimeContentType(K::HTML)
			->setPackageName('modules_catalog')
			->setDirectory('templates')
			->load('Catalog-Inc-Product-RSS');
			
		$template->setAttribute('product', $this);
		$template->setAttribute('shop', catalog_ShopService::getInstance()->getCurrentShop());
		$template->setAttribute('customer', customer_CustomerService::getInstance()->getCurrentCustomer());
		return $template->execute(true);
	}
	
	/**
	 * @see rss_Item::getRSSGuid()
	 *
	 * @return String
	 */
	public function getRSSGuid()
	{
		return LinkHelper::getDocumentUrl($this);
	}
	
	/**
	 * @see rss_Item::getRSSLabel()
	 *
	 * @return String
	 */
	public function getRSSLabel()
	{
		return $this->getLabelAsHtml();
	}
	
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
	 * @var Boolean
	 */
	private $hasNewTranslation = null;
	
	/**
	 * @return Boolean
	 */
	public function persistHasNewTranslation()
	{
		foreach ($this->getI18nInfo()->getLangs() as $lang)
		{
			if ($this->getI18nObject($lang)->isNew())
			{
				$this->hasNewTranslation = true;
				return true;
			}
		}
		$this->hasNewTranslation = false;
		return false;
	}
	
	/**
	 * @return Boolean
	 */
	public function getAndResetHasNewTranslation()
	{
		$result = $this->hasNewTranslation;
		$this->hasNewTranslation = null;
		return $result;
	}
	
	/**
	 * @param String $lang
	 * @return String
	 */
	public function isStatusModified($lang)
	{
		return $this->getI18nObject($lang)->isPropertyModified('publicationstatus');
	}
	
	/**
	 * @return string
	 */
	public function getOrderLabel()
	{
		return $this->getLabel();
	}
	
	/**
	 * @return string HTMLFragment
	 */
	public function getOrderLabelAsHtml()
	{
		return LinkHelper::getLink($this);	
	}
	
	/**
	 * @return boolean
	 */
	public function isPricePanelEnabled()
	{
		return true;
	}
	
	/**
	 * @return string
	 */
	public function getPricePanelDisabledMessage()
	{
		return null;
	}
}
