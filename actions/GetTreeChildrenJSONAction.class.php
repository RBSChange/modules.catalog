<?php
/**
 * @package modules.catalog
 */
class catalog_GetTreeChildrenJSONAction extends generic_GetTreeChildrenJSONAction
{
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param string[] $subModelNames
	 * @param string $propertyName
	 * @return array<f_persistentdocument_PersistentDocument>
	 */
	protected function getVirtualChildren($document, $subModelNames, $propertyName)
	{
		if ($document instanceof catalog_persistentdocument_product)
		{
			return catalog_PriceService::getInstance()->createQuery()->add(Restrictions::eq('productId', $document->getId()))->find();
		}
		else
		{
			return parent::getVirtualChildren($document, $subModelNames, $propertyName);
		}
	}
}
