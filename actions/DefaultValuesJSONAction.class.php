<?php
class catalog_DefaultValuesJSONAction extends generic_DefaultValuesJSONAction
{
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param String[]
	 * @return Array
	 */
	protected function exportFieldsData($document, $allowedProperties)
	{
		if ($document instanceof catalog_persistentdocument_price)
		{
			$request = $this->getContext()->getRequest();
			if ($request->hasParameter('shopId'))
			{
				$shopId = intval($request->getParameter('shopId'));
				$document->setShopId($shopId);
			}
			if ($request->hasParameter('targetId'))
			{
				$shopId = intval($request->getParameter('targetId'));
				$document->setTargetId($shopId);
			}
		}
		return parent::exportFieldsData($document, $allowedProperties);
	}
}