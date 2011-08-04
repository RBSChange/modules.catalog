<?php
/**
 * This strategy should always be the last strategy applied
 * @package modules.catalog
 */
class catalog_VirtualProductSecuremediaStrategy extends media_DisplaySecuremediaStrategy
{
	/**
	 * @param media_persistentdocument_securemedia $media
	 * @return Integer
	 */
	public function canDisplayMedia($media)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . ' : ' . $media->__toString());
		}
		
		if (!ModuleService::getInstance()->isInstalled('customer'))
		{
			return self::NOT_CONCERNED;
		}

		$productIds = catalog_VirtualproductService::getInstance()->createQuery()->setProjection(Projections::property('id'))
			->add(Restrictions::eq('secureMedia', $media))
			->findColumn('id');

		if (count($productIds) == 0 )
		{
			return self::NOT_CONCERNED;
		}
		
		$customer = customer_CustomerService::getInstance()->getCurrentCustomer();
		if ($customer === null)
		{
			return self::KO;
		}
		
		$trakingNumber = change_Controller::getInstance()->getContext()->getRequest()->getParameter('trakingNumber', null);
		if (f_util_StringUtils::isEmpty($trakingNumber))
		{
			return self::KO;
		}
		
		
		$query = order_ExpeditionService::getInstance()->createQuery()
			->setProjection(Projections::property('id', 'expeditionId'))
			->add(Restrictions::eq('trackingNumber', $trakingNumber))
			->add(Restrictions::eq('order.customer', $customer));
			
		$query->createCriteria('line')
			->setProjection(Projections::property('id', 'expeditionLineId'))
			->createPropertyCriteria('orderlineid', 'modules_order/orderline')
				->setProjection(Projections::property('productId'))
				->add(Restrictions::in('productId', $productIds));
			
		$ids = $query->findUnique();
			
		if ($ids === null)
		{
			return self::KO;
		}
		
		$expeditionId = $ids['expeditionId'];
		$expeditionLineId = $ids['expeditionLineId'];
		$productId = $ids['productId'];
		$product = DocumentHelper::getDocumentInstance($productId);
		Framework::info($product->__toString());
		if ($product instanceof catalog_persistentdocument_virtualproduct)
		{
			if (catalog_VirtualproductService::getInstance()->hasMediaAccess($product, $customer, $expeditionId, $expeditionLineId))
			{
				return self::OK;	
			}
		}
		return self::KO;	
	}
}