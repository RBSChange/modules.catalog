<?php
/**
 * catalog_persistentdocument_product
 * @package modules.catalog
 */
class catalog_persistentdocument_product extends catalog_persistentdocument_productbase implements rss_Item 
{
	const RATING_META_KEY = 'ratingMetaKey';
	
	/**
	 * @param Double $rating
	 * @param Integer $websiteid
	 */
	public function setRatingMetaForWebsiteid($rating, $websiteid)
	{
		if ($this->hasMeta(self::RATING_META_KEY))
		{
			$ratings = $this->getMetaMultiple(self::RATING_META_KEY);
		}
		else 
		{
			$ratings = array();
		}
		$ratings[$websiteid] = $rating;
		$this->setMetaMultiple(self::RATING_META_KEY, $ratings);
	}
	
	/**
	 * @param Double $rating
	 * @param Integer $websiteid
	 */
	private function getRatingMetaForWebsiteid($websiteid)
	{
		if (!$this->hasMeta(self::RATING_META_KEY))
		{
			return $this->getDocumentService()->getRatingAverage($this, $websiteid);
		}
		$ratings = $this->getMetaMultiple(self::RATING_META_KEY);
		return isset($ratings[$websiteid]) ? $ratings[$websiteid] : null;
	}
	
	/**
	 * Get the indexable document
	 * @param catalog_persistentdocument_compiledproduct $compildedProduct
	 * @return indexer_IndexedDocument
	 */
	public function getIndexedDocumentByCompiledProduct($compildedProduct)
	{
		try 
		{
			$topic = $compildedProduct->getTopic();		
			$topShelf = $compildedProduct->getTopShelf();						
			$indexedDoc = new indexer_IndexedDocument();
			$indexedDoc->setStringField('documentFamily', 'products');
			$indexedDoc->setLabel($this->getLabel());
			$indexedDoc->setLang($compildedProduct->getLang());
			$indexedDoc->setText(
				f_util_StringUtils::htmlToText($this->getDescription())
				. ' ' . $this->getBrandLabel()
				. ' ' . $topic->getLabel()
				. ' ' . $topShelf->getLabel()
				. ' ' . $this->getCodeReference()
			);					
		} 
		catch (Exception $e) 
		{
			Framework::exception($e);
			$indexedDoc = null;
		}
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
	 * @return string
	 */
	public function getCartLineKey()
	{
		return $this->getId();
	}

	/**
	 * @param integer $maxCount
	 * @return String
	 */
	public function getShortDescription($maxCount = 80)
	{
		$desc = f_util_StringUtils::htmlToText($this->getDescription(), false);
		$desc = f_util_StringUtils::shortenString($desc, $maxCount);
		return f_util_HtmlUtils::textToHtml($desc);
	}

	/**
	 * @return String
	 */
	public function getLinkTitle()
	{
		return f_util_HtmlUtils::textToHtml(LocaleService::getInstance()->transFO('m.catalog.frontoffice.titlelinkdetailproduct', array('ucf'), array('name' => $this->getNavigationLabel())));
	}

	/**
	 * @param website_pesistentdocument_website $website
	 * @return catalog_persistentdocument_shelf
	 */
	public function getBoPrimaryShelf()
	{
		return $this->getDocumentService()->getBoPrimaryShelf($this);
	}
	
	/**
	 * @param website_pesistentdocument_website $website
	 * @return catalog_persistentdocument_shelf
	 */
	public function getShopPrimaryShelf($shop)
	{
		return $this->getDocumentService()->getShopPrimaryShelf($this, $shop);
	}
	
	/**
	 * @param website_pesistentdocument_website $website
	 * @return catalog_persistentdocument_shelf
	 */
	public function getBoPrimaryTopShelf()
	{
		$shelf = $this->getDocumentService()->getBoPrimaryShelf($this);
		return catalog_ShelfService::getInstance()->getTopShelfByShelf($shelf);
	}
	
	/**
	 * @param website_pesistentdocument_website $website
	 * @return catalog_persistentdocument_shelf
	 */
	public function getShopPrimaryTopShelf($shop = null)
	{
		if ($shop === null)
		{
			$shop = catalog_ShopService::getInstance()->getCurrentShop();
		}
		$shelf = $this->getDocumentService()->getShopPrimaryShelf($this, $shop);
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
	 * @param catalog_persistentdocument_shop $shop
	 * @return media_persistentdocument_media
	 */
	public function getListVisual($shop)
	{
		return $this->getDocumentService()->getListVisual($this, $shop);
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
	 * @var float
	 */
	private $ratingAverage;
	
	/**
	 * @return String
	 */
	public function hasRating()
	{
		return ($this->getRatingAverage() > 0);
	}
	
	/**
	 * @return String
	 */
	public function getRatingAverage()
	{
		if ($this->ratingAverage === null)
		{
			$website = website_WebsiteModuleService::getInstance()->getCurrentWebsite();
			$this->ratingAverage = floatval($this->getRatingMetaForWebsiteid($website->getId()));
		}
		return $this->ratingAverage;
	}
	
	/**
	 * @return String
	 */
	public function getFormattedRatingAverage()
	{
		$ratingAverage = $this->getRatingAverage();
		if ($ratingAverage === null)
		{
			return LocaleService::getInstance()->transFO('m.catalog.frontoffice.no-rating-yet', array('ucf', 'html'));
		}
		else
		{
			return number_format($ratingAverage, 1);
		}
	}
	
	/**
	 * @var integer
	 */
	private $commentCount;
	
	/**
	 * @return boolean
	 */
	public function hasComment()
	{
		return ($this->getCommentCount() > 0);
	}
	
	public function getCommentCount()
	{
		if ($this->commentCount === null)
		{
			$this->commentCount = comment_CommentService::getInstance()->getPublishedCountByTargetId($this->getId());
		}
		return $this->commentCount;
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @param catalog_persistentdocument_billingarea $billingArea
	 * @param customer_persistentdocument_customer $customer nullable
	 * @param Double $quantity
	 * @return catalog_persistentdocument_price
	 */
	public function getPrice($shop, $billingArea, $customer, $quantity = 1)
	{
		$targetIds = catalog_PriceService::getInstance()->convertCustomerToTargetIds($customer);
		return $this->getDocumentService()->getPriceByTargetIds($this, $shop, $billingArea, $targetIds, $quantity);
	}
	
	/**
	 * @return String
	 */
	public function getFormattedCurrentShopPrice()
	{
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		$billingArea = $shop->getCurrentBillingArea();
		$price = $this->getPrice($shop, $billingArea, null);
		if ($price !== null)
		{
			return $price->getFormattedValueWithTax();
		}
		return null;
	}
	
	/**
	 * @return catalog_persistentdocument_price
	 */
	public function getPriceForCurrentShopAndCustomer()
	{
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		$billingArea = $shop->getCurrentBillingArea();
		$customer = customer_CustomerService::getInstance()->getCurrentCustomer();
		return $this->getPrice($shop, $billingArea, $customer);
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @param catalog_persistentdocument_billingarea $billingArea
	 * @param customer_persistentdocument_customer $customer
	 * @return catalog_persistentdocument_price[]
	 */
	public function getPrices($shop, $billingArea, $customer)
	{
		$targetIds = catalog_PriceService::getInstance()->convertCustomerToTargetIds($customer);
		return $this->getDocumentService()->getPricesByTargetIds($this, $shop, $billingArea, $targetIds);
	}
	
	/**
	 * @return catalog_persistentdocument_price[]
	 */
	public function getPricesForCurrentShopAndCustomer()
	{
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		$billingArea = $shop->getCurrentBillingArea();
		$customer = customer_CustomerService::getInstance()->getCurrentCustomer();
		return $this->getPrices($shop, $billingArea, $customer);
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
	
	// Gestion du stock du produit
	/**
	 * @return catalog_StockableDocument
	 */
	public function getStockableDocument()
	{
		return catalog_StockService::getInstance()->getStockableDocument($this);
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @param double $quantity
	 * @return Boolean
	 */
	public function isAvailable($shop = null, $quantity = 1)
	{
		return catalog_StockService::getInstance()->isAvailable($this, $quantity, $shop);
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return String
	 */
	public function getAvailability($shop = null)
	{
		return catalog_StockService::getInstance()->getAvailability($this, $shop);
	}
	
	/**
	 * @param double $quantity
	 * @return double | null new quantity
	 */
	public function addStockQuantity($quantity)
	{
		return $this->getDocumentService()->addStockQuantity($this, $quantity);
	}

	/**
	 * @return double | null
	 */
	public function getCurrentStockQuantity()
	{
		return $this->getDocumentService()->getCurrentStockQuantity($this);
	}
	
	/**
	 * @return string
	 */
	public function getCurrentStockLevel()
	{
		return $this->getDocumentService()->getCurrentStockLevel($this);
	}
	
	/**
	 * @var boolean
	 */
	private $sendStockAlert = false;
	
	public function setMustSendStockAlert($sendStockAlert = true)
	{
		$this->sendStockAlert = $sendStockAlert;
	}
		
	/**
	 * @return boolean
	 */
	public function mustSendStockAlert()
	{
		return $this->sendStockAlert;
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
	 * @param int $quantity
	 * @return Boolean
	 */
	public function canBeOrdered($shop, $quantity = 1)
	{
		$cms = catalog_ModuleService::getInstance();
		return $cms->isCartEnabled() && ($shop->getAllowOrderOutOfStock() || $this->isAvailable($shop, $quantity));
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @param String $type from list 'modules_catalog/crosssellingtypes'
	 * @return integer[]
	 */
	public function getDisplayableCrossSellingIds($shop, $type = 'complementary')
	{
		return $this->getDocumentService()->getDisplayableCrossSellingIds($this, $shop, $type);
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
	 * @return String
	 */
	public function getRSSDate()
	{
		return $this->getModificationdate();
	}
	
	/**
	 * @see rss_Item::getRSSDescription()
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
	 * @return string
	 */
	public function getRSSGuid()
	{
		return LinkHelper::getPermalink($this);
	}
	
	/**
	 * @see rss_Item::getRSSLink()
	 * @return string
	 */
	public function getRSSLink()
	{
		return LinkHelper::getDocumentUrl($this);
	}
	
	/**
	 * @see rss_Item::getRSSLabel()
	 * @return String
	 */
	public function getRSSLabel()
	{
		return $this->getLabelAsHtml();
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
		return $this->getNavigationLabel();
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
	
	/**
	 * @return boolean
	 */
	public function hasPublishedBrand()
	{
		$brand = $this->getBrand();
		return ($brand instanceof brand_persistentdocument_brand && $brand->isPublished());
	}

	/**
	 * @return boolean
	 */
	public function updateCartQuantity()
	{
		return true;
	}	

	/**
	 * @return shipping_persistentdocument_mode
	 */
	public function getShippingMode()
	{
		if (intval($this->getShippingModeId()) > 0)
		{
			try 
			{
				return DocumentHelper::getDocumentInstance($this->getShippingModeId(), 'modules_shipping/mode');
			}
			catch (Exception $e)
			{
				Framework::warn($e->getMessage());
			}
		}
		return null;
	}
	
	/**
	 * @param shipping_persistentdocument_mode $mode
	 */
	public function setShippingMode($mode)
	{
		if ($mode instanceof shipping_persistentdocument_mode)
		{
			$this->setShippingModeId($mode->getId());
		}
	}
	
	/**
	 * @return boolean
	 */
	public function handleRelatedProducts()
	{
		return true;
	}
	
	public function getContextualTopicId()
	{
		$pageId = website_WebsiteModuleService::getInstance()->getCurrentPageId();
		if ($pageId)
		{
			$ancestors = website_WebsiteModuleService::getInstance()->getCurrentPageAncestors();
			$topic = f_util_ArrayUtils::lastElement($ancestors);
			if ($topic instanceof website_persistentdocument_systemtopic)
			{
				$result = catalog_CompiledproductService::getInstance()->createQuery()
					->add(Restrictions::eq('primary', false))
					->add(Restrictions::eq('lang', RequestContext::getInstance()->getLang()))
					->add(Restrictions::eq('topicId', $topic->getId()))
					->add(Restrictions::eq('product', $this))
					->setProjection(Projections::property('id', 'id'))
					->findUnique();
				if (is_array($result))
				{
					return $topic->getId();
				}
			}
		}
		return  null;		
	}
	
	/**
	 * @return catalog_persistentdocument_compiledproduct | null
	 */
	public function getContextualCompiledProduct()
	{
		$pageId = website_WebsiteModuleService::getInstance()->getCurrentPageId();
		if ($pageId)
		{
			$ancestors = website_WebsiteModuleService::getInstance()->getCurrentPageAncestors();
			$topic = f_util_ArrayUtils::lastElement($ancestors);
			if ($topic instanceof website_persistentdocument_systemtopic)
			{
				return catalog_CompiledproductService::getInstance()->createQuery()
					->add(Restrictions::eq('lang', RequestContext::getInstance()->getLang()))
					->add(Restrictions::eq('topicId', $topic->getId()))
					->add(Restrictions::eq('product', $this))->findUnique();
			}
		}
		return  null;
	}

	/**
	 * @return catalog_persistentdocument_compiledproduct | null
	 */
	public function getPrimaryContextualCompiledProduct()
	{
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		if ($shop instanceof  catalog_persistentdocument_shop)
		{
			return catalog_CompiledproductService::getInstance()->createQuery()
					->add(Restrictions::eq('lang', RequestContext::getInstance()->getLang()))
					->add(Restrictions::eq('primary', true))
					->add(Restrictions::eq('shopId', $shop->getId()))
					->add(Restrictions::eq('product', $this))
					->findUnique();
		}
		return  null;
	}
	// Deprecated 
	
	/**
	 * @deprecated (will be removed in 4.0) use getBoPrimaryShelf or getShopPrimaryShelf instead
	 */
	public function getPrimaryShelf($website = null)
	{
		if ($website === null)
		{
			return $this->getBoPrimaryShelf();
		}
		else 
		{
			$shop = catalog_ShopService::getInstance()->getDefaultByWebsite($website);
			return $this->getShopPrimaryShelf($shop);
		}
	}
	
	/**
	 * @deprecated (will be removed in 4.0) use getBoPrimaryTopShelf or getShopPrimaryTopShelf instead
	 */
	public function getPrimaryTopShelf($website = null)
	{
		if ($website === null)
		{
			return $this->getBoPrimaryTopShelf();
		}
		else 
		{
			$shop = catalog_ShopService::getInstance()->getDefaultByWebsite($website);
			return $this->getShopPrimaryTopShelf($shop);
		}
	}
	
	/**
	 * @deprecated use getDisplayableCrossSellingIds
	 */
	public function getDisplayableCrossSelling($shop, $type = 'complementary', $sortBy = 'fieldorder')
	{
		return $this->getDocumentService()->getDisplayableCrossSelling($this, $shop, $type, $sortBy);
	}
}