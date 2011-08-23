<?php
/**
 * @package module.catalog
 */
class catalog_ListDeclinationsbyproductService extends BaseService
{
	/**
	 * @var catalog_DeclinationsbyproductService
	 */
	private static $instance;
	
	/**
	 * @return catalog_DeclinationsbyproductService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = new self();
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
	 * @return String
	 */
	public final function getDefaultId()
	{
		return 'none';
	}
}