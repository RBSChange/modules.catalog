<?php
/**
 * catalog_persistentdocument_simpleproduct
 * @package catalog.persistentdocument
 */
class catalog_persistentdocument_simpleproduct extends catalog_persistentdocument_simpleproductbase implements catalog_StockableDocument
{
	/**
	 * @return String
	 */
	public function getDetailBlockName()
	{
		return 'simpleproduct';
	}
	
	/**
	 * @see f_persistentdocument_PersistentDocumentImpl::addTreeAttributes()
	 *
	 * @param string $moduleName
	 * @param string $treeType
	 * @param unknown_type $nodeAttributes
	 */
	protected function addTreeAttributes($moduleName, $treeType, &$nodeAttributes)
	{
		parent::addTreeAttributes($moduleName, $treeType, $nodeAttributes);
		
		if ($treeType == 'wlist')
		{
			$detailVisual = $this->getVisual();
			if ($detailVisual)
			{
				$nodeAttributes['thumbnailsrc'] = MediaHelper::getPublicFormatedUrl($this->getVisual(), "modules.uixul.backoffice/thumbnaillistitem");
			}
		}
	}
	
	
	//catalog_StockableDocument

	private $mustSendStockAlert = false;
	
	/**
	 * @return Double
	 */
	private function getAlertThreshold()
	{
		if ($this->getStockAlertThreshold() !== null)
		{
			return $this->getStockAlertThreshold();
		}
		return ModuleService::getInstance()->getPreferenceValue('catalog', 'stockAlertThreshold');
	}
	
	protected function updateStockLevel()
	{
		if ($this->isAvailable(null))
		{
			$stockLevel = $this->getStockLevel();
			if ($stockLevel == catalog_StockService::LEVEL_UNAVAILABLE || $stockLevel === null)
			{
				$this->setStockLevel(catalog_StockService::LEVEL_AVAILABLE);
			}
		}
		else
		{
			$this->setStockLevel(catalog_StockService::LEVEL_UNAVAILABLE);
		}		
	}
	
	/**
	 * @see catalog_persistentdocument_simpleproductbase::setStockQuantity()
	 *
	 * @param Double $stockQuantity
	 */
	public function setStockQuantity($stockQuantity)
	{
		parent::setStockQuantity($stockQuantity);
		$this->updateStockLevel();
	}

	/**
	 * @see catalog_StockableDocument::addStockQuantity()
	 *
	 * @param Double $quantity
	 * @return Double
	 */
	function addStockQuantity($quantity)
	{
		$oldQuantity = $this->getStockQuantity();
		if ($oldQuantity !== null)
		{
			$newQuantity = $oldQuantity + $quantity;		
			$this->setStockQuantity($newQuantity);
			if ($quantity < 0)
			{
				$theshold = $this->getAlertThreshold();
				$this->mustSendStockAlert = ($oldQuantity > $theshold && $newQuantity <= $theshold);			
			}
			return $newQuantity;
		}
		$this->updateStockLevel();	
		return $oldQuantity;
	}
	
	/**
	 * @see catalog_StockableDocument::mustSendStockAlert()
	 *
	 * @return boolean
	 */
	function mustSendStockAlert()
	{
		$mustSendStockAlert = $this->mustSendStockAlert;
		$this->mustSendStockAlert = false;
		return $mustSendStockAlert;
	}
}