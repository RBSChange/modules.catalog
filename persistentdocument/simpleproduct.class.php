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
}