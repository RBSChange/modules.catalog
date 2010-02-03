<?php
/**
 * catalog_ShippingfilterService
 * @package catalog
 */
class catalog_ShippingfilterService extends f_persistentdocument_DocumentService
{
	/**
	 * @var catalog_ShippingfilterService
	 */
	private static $instance;

	/**
	 * @return catalog_ShippingfilterService
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
	 * @return catalog_persistentdocument_shippingfilter
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_catalog/shippingfilter');
	}

	/**
	 * Create a query based on 'modules_catalog/shippingfilter' model.
	 * Return document that are instance of modules_catalog/shippingfilter,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_catalog/shippingfilter');
	}
	
	/**
	 * Create a query based on 'modules_catalog/shippingfilter' model.
	 * Only documents that are strictly instance of modules_catalog/shippingfilter
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_catalog/shippingfilter', false);
	}
	

	/**
	 * @param catalog_persistentdocument_shippingfilter $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function preInsert($document, $parentNodeId = null)
	{
		$document->setInsertInTree(false);
		if ($document->getShop() === null)
		{
			$document->setShop($this->pp->getDocumentInstance($parentNodeId, 'modules_catalog/shop'));
		}
	}
	
	/**
	 * @param catalog_persistentdocument_shippingfilter $document
	 * @param Integer $parentNodeId
	 */
	protected function preSave($document, $parentNodeId) 
	{
		if ($document->getTaxCode() === null)
		{
			$document->setTaxCode('0');
		}
		$document->setValueWithoutTax(catalog_PriceHelper::removeTax($document->getValueWithTax(), $document->getTaxCode()));
	}

	/**
	 * @param order_CartInfo $cart
	 * @return catalog_persistentdocument_shippingfilter[]
	 */
	public function getCurrentShippingModes($cart)
	{
		$filters = $this->createQuery()->add(Restrictions::published())
			->add(Restrictions::eq('shop', $cart->getShop()))->find();
		$result = array();
		foreach ($filters as $filter) 
		{
			if ($this->isValidShippingFilter($filter, $cart))
			{
				$result[] = $filter;
			} 
		}
		return $result;
	}
	
	/**
	 * @param catalog_persistentdocument_shippingfilter $filter
	 * @param order_CartInfo $cart
	 * @return boolean
	 */
	public function isValidShippingFilter($filter, $cart)
	{
		if ($filter->isPublished() && $filter->getMode()->isPublished())
		{
			$df = f_persistentdocument_DocumentFilterService::getInstance();
			if ($df->checkValueFromJson($filter->getQuery(), $cart))
			{
				return true;
			}
		}
		return false;
	}
}