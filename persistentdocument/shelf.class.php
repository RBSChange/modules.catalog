<?php
/**
 * catalog_persistentdocument_shelf
 * @package modules.catalog
 */
class catalog_persistentdocument_shelf extends catalog_persistentdocument_shelfbase
{
	/**
	 * @return String
	 */
	public function getVisualURL()
	{
		$visual = $this->getVisual();
		if ($visual === null)
		{
			return "";
		}
		return LinkHelper::getDocumentUrl($visual);
	}
	
	/**
	 * @return array<catalog_persistentdocument_shelf>
	 */
	public function getPublishedSubShelves()
	{
		return $this->getDocumentService()->getPublishedSubShelves($this);
	}
	
	/**
	 * @return array<catalog_persistentdocument_shelf>
	 */
	public function getPublishedSubShelvesInCurrentShop()
	{
		return $this->getDocumentService()->getPublishedSubShelvesInCurrentShop($this);
	}
	
	/**
	 * @return Boolean
	 */
	public function isTopShelf()
	{
		return false;
	}
			
	/**
	 * @return String
	 */
	public function getPathForUrl()
	{
		return catalog_ReferencingService::getInstance()->getPathForUrlByShelf($this);
	}
	
	/**
	 * @return String
	 */
	public function getShortDescription()
	{
		$desc = f_util_StringUtils::htmlToText($this->getDescription(), false);
		$desc = f_util_StringUtils::shortenString($desc, 80);
		return f_util_HtmlUtils::textToHtml($desc);
	}
	
	/**
	 * @return catalog_persistentdocument_shelf
	 */
	public function getParentShelf()
	{
		$parent = $this->getDocumentService()->getParentOf($this);
		if ($parent instanceof catalog_persistentdocument_shelf)
		{
			return $parent;
		}
		return null;
	}
	
	/**
	 * @return String[]
	 */
	public function getNewTranslationLangs()
	{
		$langs = array();
		foreach ($this->getI18nInfo()->getLangs() as $lang)
		{
			if ($this->getI18nObject($lang)->isNew())
			{
				$langs[] = $lang;
			}
		}
		return $langs;
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @param string $lang
	 * @return integer
	 */
	public function getPublishedProductCount($shop = null, $lang = null)
	{
		if ($shop === null)
		{
			$shop = catalog_ShopService::getInstance()->getCurrentShop();
		}
		return $this->getDocumentService()->getPublishedProductCount($this, $shop, $lang);
	}
	
	/**
	 * @return website_persistentdocument_systemtopic || null
	 */
	public function getContextualSystemTopic()
	{
		$pageId = website_WebsiteModuleService::getInstance()->getCurrentPageId();
		if ($pageId)
		{
			$ancestors = website_WebsiteModuleService::getInstance()->getCurrentPageAncestors();
			$topic = f_util_ArrayUtils::lastElement($ancestors);
			if ($topic instanceof website_persistentdocument_systemtopic)
			{
				return website_SystemtopicService::getInstance()->createQuery()
					->add(Restrictions::descendentOf($topic->getId()))
					->add(Restrictions::eq('referenceId', $this->getId()))->findUnique();
			}
		}
		return null;
	}

	/**
	 * @return website_persistentdocument_systemtopic || null
	 */
	public function getDefaultContextualSystemTopic()
	{
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		if ($shop instanceof catalog_persistentdocument_shop)
		{
			$topic = $shop->getTopic();
			return website_SystemtopicService::getInstance()->createQuery()
					->add(Restrictions::descendentOf($topic->getId()))
					->add(Restrictions::eq('referenceId', $this->getId()))->findUnique();
		}
		return  null;
	}
	
	//DEPRECATED
	
	/**
	 * @deprecated
	 */
	public function isVisible()
	{
		return true;
	}
	
	/**
	 * @deprecated use LinkHelper::getDocumentUrl
	 */
	public function getUrl()
	{
		return LinkHelper::getDocumentUrl($this);
	}
}