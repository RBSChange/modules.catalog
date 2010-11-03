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
	 * @return String
	 */
	public function getUrl()
	{
		return LinkHelper::getDocumentUrl($this);
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
	 * Return true if the shelf is a top shelf else return false
	 * @return Boolean
	 */
	public function isTopShelf()
	{
		if (is_null($this->getParentShelf()))
		{
			return true;
		}
		return false;
	}
		
	/**
	 * @return Boolean
	 */
	public function isVisible()
	{
		return WebsiteHelper::isVisible($this->getTopic());
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
		$desc = str_replace(array('&', '<', '>', '\'', '"'), array('&amp;', '&lt;', '&gt;', '&#39;', '&quot;'), $desc);
		return $desc;
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
	 * @see f_persistentdocument_PersistentDocumentImpl::addTreeAttributes()
	 * @param string $moduleName
	 * @param string $treeType
	 * @param unknown_type $nodeAttributes
	 */
	protected function addTreeAttributes($moduleName, $treeType, &$nodeAttributes)
	{
		if ($treeType == 'wlist')
		{
			$detailVisual = $this->getVisual();
			if ($detailVisual)
			{
				$nodeAttributes['thumbnailsrc'] = MediaHelper::getPublicFormatedUrl($this->getVisual(), "modules.uixul.backoffice/thumbnaillistitem");
			}
		}	
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
}