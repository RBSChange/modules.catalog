<?php
class catalog_DefaultValuesJSONAction extends generic_DefaultValuesJSONAction
{
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param string[] $allowedProperties
	 * @param integer $parentId
	 * @return array
	 */
	protected function exportFieldsData($document, $allowedProperties, $parentId = null)
	{
		if ($document instanceof catalog_persistentdocument_price)
		{
			$request = $this->getContext()->getRequest();
			
			if ($request->hasParameter('shopId'))
			{
				$shop = catalog_persistentdocument_shop::getInstanceById(intval($request->getParameter('shopId')));
				$document->setShopId($shop->getId());
				if (!$request->hasParameter('billingAreaId'))
				{
					$billingArea = $shop->getDefaultBillingArea();
					$document->setBillingAreaId($billingArea->getId());
					$document->setStoreWithTax($billingArea->getStorePriceWithTax());	
				}		
			}
			
			if ($request->hasParameter('billingAreaId'))
			{
				$billingArea = catalog_persistentdocument_billingarea::getInstanceById(intval($request->getParameter('billingAreaId')));
				$document->setBillingAreaId($billingArea->getId());
				$document->setStoreWithTax($billingArea->getStorePriceWithTax());
			}
			
			if ($request->hasParameter('targetId'))
			{
				$targetId = intval($request->getParameter('targetId'));
				$document->setTargetId($targetId);
			}
		}
		return parent::exportFieldsData($document, $allowedProperties, $parentId);
	}
}