<?php
/**
 * @package module.catalog
 */
class catalog_ListShippingfiltersbyshopService extends BaseService
{
	/**
	 * @var catalog_ListShippingFiltersService
	 */
	private static $instance;

	/**
	 * @return catalog_ListShippingFiltersService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}

	/**
	 * @return array<list_Item>
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
			$items[] = new list_Item(
				$filter->getLabel(),
				$filter->getId()
			);
		}
		return $items;
	}

	/**
	 * @return String
	 */
	public final function getDefaultId()
	{
		return 'none';
	}
}