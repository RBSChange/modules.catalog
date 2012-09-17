<?php
/**
 * catalog_persistentdocument_shelf
 * @package modules.catalog
 */
class catalog_persistentdocument_shelf extends catalog_persistentdocument_shelfbase
{
	/**
	 * @return string
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
	 * @return boolean
	 */
	public function isTopShelf()
	{
		return false;
	}
				
	/**
	 * @return string
	 */
	public function getShortDescription()
	{
		$desc = f_util_HtmlUtils::htmlToText($this->getDescription(), false);
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
	 * @return string[]
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
		$pageId = website_PageService::getInstance()->getCurrentPageId();
		if ($pageId)
		{
			$ancestors = website_PageService::getInstance()->getCurrentPageAncestors();
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
}