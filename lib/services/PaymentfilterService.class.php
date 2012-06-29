<?php
/**
 * @package modules.catalog
 * @method catalog_PaymentfilterService getInstance()
 */
class catalog_PaymentfilterService extends f_persistentdocument_DocumentService
{
	/**
	 * @return catalog_persistentdocument_paymentfilter
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_catalog/paymentfilter');
	}

	/**
	 * Create a query based on 'modules_catalog/paymentfilter' model.
	 * Return document that are instance of modules_catalog/paymentfilter,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->getPersistentProvider()->createQuery('modules_catalog/paymentfilter');
	}
	
	/**
	 * Create a query based on 'modules_catalog/paymentfilter' model.
	 * Only documents that are strictly instance of modules_catalog/paymentfilter
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->getPersistentProvider()->createQuery('modules_catalog/paymentfilter', false);
	}

	/**
	 * @param catalog_persistentdocument_paymentfilter $document
	 * @param integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function preInsert($document, $parentNodeId = null)
	{
		$document->setInsertInTree(false);
		if ($document->getShop() === null)
		{
			$shop = catalog_persistentdocument_shop::getInstanceById($parentNodeId);
			$document->setShop($shop);
		}
		
		if ($document->getBillingArea() === null)
		{
			$document->setBillingArea($document->getShop()->getDefaultBillingArea());
		}
	}
	
	
	/**
	 * this method is call before save the duplicate document.
	 * @param catalog_persistentdocument_paymentfilter $newDocument
	 * @param catalog_persistentdocument_paymentfilter $originalDocument
	 * @param integer $parentNodeId
	 */
	protected function preDuplicate($newDocument, $originalDocument, $parentNodeId)
	{
		$newDocument->setLabel(LocaleService::getInstance()->trans('m.generic.backoffice.duplicate-prefix', array('ucf'), array('number' => '')) . ' ' . $originalDocument->getLabel());
		$newDocument->setPublicationstatus(f_persistentdocument_PersistentDocument::STATUS_DEACTIVATED);
	}
	

	/**
	 * @param order_CartInfo $cart
	 * @return catalog_persistentdocument_paymentfilter[]
	 */
	public function getCurrentPaymentConnectors($cart)
	{
		$filters = $this->createQuery()->add(Restrictions::published())
			->add(Restrictions::eq('shop', $cart->getShop()))
			->add(Restrictions::eq('billingArea', $cart->getBillingArea()))
			->find();

		$result = array();
		foreach ($filters as $filter) 
		{
			if (isset($result[$filter->getConnector()->getId()]))
			{
				continue;
			}
			if ($this->isValidPaymentFilter($filter, $cart))
			{
				$result[$filter->getConnector()->getId()] = $filter;
			} 
		}
		return array_values($result);
	}
	
	/**
	 * @param catalog_persistentdocument_paymentfilter $filter
	 * @param order_CartInfo $cart
	 * @return boolean
	 */
	public function isValidPaymentFilter($filter, $cart)
	{
		if ($filter->isPublished() 
			&& DocumentHelper::equals($filter->getBillingArea(), $cart->getBillingArea()) 
			&& $filter->getConnector()->isPublished())
		{
			$connector = $filter->getConnector();
			if ($connector->getMaxValue() !== null)
			{
				if (($cart->getTotalAmount() - $connector->getMaxValue()) > 0)
				{
					return false;
				}
			}
			if ($connector->getMinValue() !== null)
			{
				if (($connector->getMinValue() - $cart->getTotalAmount()) > 0.01)
				{
					return false;
				}
			}
			$df = f_persistentdocument_DocumentFilterService::getInstance();
			if ($df->checkValueFromJson($filter->getQuery(), $cart))
			{
				return true;
			}
		}
		return false;
	}
	
	
}