<?php
/**
 * @package modules.catalog
 * @method catalog_ListShippingfiltersbyshopService getInstance()
 */
class catalog_ListShippingfiltersbyshopService extends change_BaseService implements list_ListItemsService
{
	/**
	 * @return list_Item[]
	 */
	public final function getItems()
	{
		try 
		{
			$request = change_Controller::getInstance()->getContext()->getRequest();
			$shopId = intval($request->getParameter('shopId', 0));
			$shop = DocumentHelper::getDocumentInstance($shopId);
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
		foreach (catalog_ShippingfilterService::getInstance()->getByShop($shop) as $filter)
		{
			$items[] = new list_Item($filter->getLabel(), $filter->getId());
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