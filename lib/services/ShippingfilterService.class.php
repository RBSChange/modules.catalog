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
			->add(Restrictions::eq('shop', $cart->getShop()))
			->add(Restrictions::eq('selectbyproduct', false))
			->addOrder(Order::asc('valueWithTax'))
			->find();
		$result = array();
		foreach ($filters as $filter) 
		{
			if (isset($result[$filter->getMode()->getId()]))
			{
				continue;
			}
			$cart->setCurrentTestFilter($filter);
			if ($this->isValidShippingFilter($filter, $cart))
			{
				$result[$filter->getMode()->getId()] = $filter;
			} 
		}
		$cart->setCurrentTestFilter(null);
		return array_values($result);
	}
	
	
	/**
	 * @param order_CartInfo $cart
	 * @return boolean
	 */
	public function setRequiredShippingModes($cart)
	{
		$ok = true;
		foreach ($cart->getRequiredShippingModeIds() as $shippingModeId) 
		{
			$filters = $this->createQuery()->add(Restrictions::published())
				->add(Restrictions::eq('shop', $cart->getShop()))
				->add(Restrictions::eq('selectbyproduct', true))
				->add(Restrictions::eq('mode.id', $shippingModeId))
				->addOrder(Order::asc('valueWithTax'))
				->find();
				
			$shippingFilter = null;	
			foreach ($filters as $filter) 
			{
				$cart->setCurrentTestFilter($filter);
				if ($this->isValidShippingFilter($filter, $cart))
				{
					$shippingFilter = $filter;
					break;
				}
			}
			$ok = $ok && ($shippingFilter !== null);
			$cart->setRequiredShippingFilter($shippingModeId, $shippingFilter);
		}
		$cart->setCurrentTestFilter(null);
		return $ok;
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
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return catalog_persistentdocument_shippingfilter[]
	 */
	public function getByShop($shop)
	{
		return catalog_ShippingfilterService::getInstance()->createQuery()
			->add(Restrictions::eq('shop', $shop))
			->addOrder(Order::iasc('document_label'))->find();
	}
	
	/**
	 * @return shipping_persistentdocument_mode[]
	 */
	public function getModesSelectedByProduct()
	{
		return catalog_ShippingfilterService::getInstance()->createQuery()->add(Restrictions::eq('selectbyproduct', true))
			->setProjection(Projections::groupProperty('mode'))->findColumn('mode');
	}
}