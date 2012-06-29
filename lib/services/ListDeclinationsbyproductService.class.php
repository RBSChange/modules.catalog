<?php
/**
 * @package modules.catalog
 * @method catalog_ListDeclinationsbyproductService getInstance()
 */
class catalog_ListDeclinationsbyproductService extends change_BaseService implements list_ListItemsService
{
	/**
	 * @return list_Item[]
	 */
	public final function getItems()
	{
		try 
		{
			$request = change_Controller::getInstance()->getContext()->getRequest();
			$productId = intval($request->getParameter('productId', 0));
			$product = DocumentHelper::getDocumentInstance($productId);
		}
		catch (Exception $e)
		{
			if (Framework::isDebugEnabled())
			{
				Framework::debug(__METHOD__ . ' EXCEPTION: ' . $e->getMessage());
			}
			return array();
		}
		
		$items = array();
		foreach ($product->getDeclinationArray() as $declination)
		{
			$items[] = new list_Item(
				$declination->getLabel(),
				$declination->getId()
			);
		}
		return $items;
	}

	/**
	 * @return string
	 */
	public final function getDefaultId()
	{
		return 'none';
	}
}