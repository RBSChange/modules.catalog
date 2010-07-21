<?php
/**
 * catalog_persistentdocument_shop
 * @package modules.catalog
 */
class catalog_persistentdocument_shop extends catalog_persistentdocument_shopbase 
{
	/**
	 * @var f_persistentdocument_PersistentDocument $parent instance of topic or website
	 */
	private $parent = null;

	/**
	 * @return f_persistentdocument_PersistentDocument instance of topic or website
	 */
	public function getMountParent()
	{
		if ($this->parent !== null)
		{
			return $this->parent;
		}
		$topic = $this->getTopic();
		if ($topic !== null)
		{
			return $topic->getDocumentService()->getParentOf($topic);
		}
		return null;
	}
	
	/**
	 * @return Integer
	 */
	public function getMountParentId()
	{
		$parent = $this->getMountParent();
		return ($parent === null) ? null : $parent->getId();
	}
	
	/**
	 * @param Integer $parentId
	 */
	public function setMountParentId($parentId)
	{
		$parent = null;
		if ($parentId !== null)
		{
			$parent = DocumentHelper::getDocumentInstance($parentId);
		}
		$this->parent = $parent;
		$this->setModificationdate(null);
	}

	/**
	 * @return String
	 * @throw catalog_Exception
	 */
	public function getCurrencySymbol()
	{
		return catalog_ShopService::getInstance()->getCurrencySymbol($this);
	}
	
	/**
	 * @return String
	 */
	public function getPriceModeLabel()
	{
		if ($this->getPriceMode() === catalog_PriceHelper::MODE_B_TO_C)
		{
			return f_Locale::translateUI('&modules.catalog.document.price.WithTax;');
		}
		else
		{
			return f_Locale::translateUI('&modules.catalog.document.price.WithoutTax;');
		}
	}
	
	/**
	 * @param string $moduleName
	 * @param string $treeType
	 * @param array<string, string> $nodeAttributes
	 */
	protected function addTreeAttributes($moduleName, $treeType, &$nodeAttributes)
	{
		$nodeAttributes['topicId'] = $this->getTopic()->getId();
		if ($treeType === 'wlist')
		{
			$nodeAttributes['website'] = $this->getWebsite()->getLabel();
		}
	}
	
	/**
	 * @param double $value
	 * @return string
	 */
	public function formatPrice($value)
	{
		return catalog_PriceHelper::applyFormat($value, $this->getDocumentService()->getPriceFormat($this));
	}
	
	/**
	 * @param double $value
	 * @return string
	 */	
	public function formatTaxRate($taxRate)
	{
		return catalog_PriceHelper::formatTaxRate($taxRate);
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
}