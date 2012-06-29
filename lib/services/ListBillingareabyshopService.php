<?php
/**
 * @package modules.catalog
 * @method catalog_ListBillingareabyshopService getInstance()
 */
class catalog_ListBillingareabyshopService extends change_BaseService implements list_ListItemsService
{
	/**
	 * @return list_Item[]
	 */
	public final function getItems()
	{
		$request = change_Controller::getInstance()->getContext()->getRequest();
		$shopId = intval($request->getParameter('shopId', 0));
		$shop = DocumentHelper::getDocumentInstanceIfExists($shopId);
		if ($shop instanceof catalog_persistentdocument_shop)
		{
			$array  = $shop->getBillingAreasArray();
		}
		else
		{
			$array = catalog_BillingareaService::getInstance()->createQuery()->find();
		}
		
		$items = array();
		foreach ($array as $ba)
		{
			/* @var $ba catalog_persistentdocument_billingarea */
			$items[] = new list_Item($ba->getLabel(), $ba->getId());
		}
		return $items;
	}
	
	/**
	 * @see list_persistentdocument_dynamiclist::getItemByValue()
	 * @param string $value;
	 * @return list_Item
	 */
	public function getItemByValue($value)
	{
		$item = null;
		$ba = DocumentHelper::getDocumentInstanceIfExists($value);
		if ($ba instanceof catalog_persistentdocument_billingarea)
		{
			$item = new list_Item($ba->getLabel(), $ba->getId());
		}
		return $item;
	}

	/**
	 * @var Array
	 */
	private $parameters = array();
	
	/**
	 * @see list_persistentdocument_dynamiclist::getListService()
	 * @param array $parameters
	 */
	public function setParameters($parameters)
	{
		$this->parameters = $parameters;
	}
}