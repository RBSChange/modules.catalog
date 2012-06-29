<?php
class catalog_BlockDashboardGeneralStatisticsAction extends dashboard_BlockDashboardAction
{	
	/**
	 * @param f_mvc_Request $request
	 * @param boolean $forEdition
	 */
	protected function setRequestContent($request, $forEdition)
	{
		if ($forEdition)
		{
			return;
		}
		
		$shopCount = $this->getCount(catalog_ShopService::getInstance(), false);
		$publishedShopCount = $this->getCount(catalog_ShopService::getInstance());
		$todayCreatedShopCount = $this->getTodayCount(catalog_ShopService::getInstance(), true);
		$todayModifiedShopCount = $this->getTodayCount(catalog_ShopService::getInstance());
		
		$shelfCount = $this->getCount(catalog_ShelfService::getInstance(), false);
		$publishedShelfCount = $this->getCount(catalog_ShelfService::getInstance());
		$todayCreatedShelfCount = $this->getTodayCount(catalog_ShelfService::getInstance(), true);
		$todayModifiedShelfCount = $this->getTodayCount(catalog_ShelfService::getInstance());

		$productCount = $this->getCount(catalog_ProductService::getInstance(), false);
		$publishedProductCount = $this->getCount(catalog_ProductService::getInstance());
		$todayCreatedProductCount = $this->getTodayCount(catalog_ProductService::getInstance(), true);
		$todayModifiedProductCount = $this->getTodayCount(catalog_ProductService::getInstance());
		
		$toRecompile = catalog_ProductService::getInstance()->getCountProductIdsToCompile();
		$widget = array(
			'lines'   => array(
				array(LocaleService::getInstance()->trans('m.catalog.bo.dashboard.shop-count', array('ucf')), 
					$publishedShopCount, $shopCount, $todayCreatedShopCount, $todayModifiedShopCount, ''),
				array(LocaleService::getInstance()->trans('m.catalog.bo.dashboard.shelves-count', array('ucf')), 
					$publishedShelfCount, $shelfCount, $todayCreatedShelfCount, $todayModifiedShelfCount, ''),
				array(LocaleService::getInstance()->trans('m.catalog.bo.dashboard.products-count', array('ucf')), 
					$publishedProductCount, $productCount, $todayCreatedProductCount, $todayModifiedProductCount, $toRecompile),
			)
		);
			
		$request->setAttribute('widget', $widget);
	}
	
	/**
	 * @param DocumentService $service
	 * @param boolean $published
	 * @return integer
	 */
	private function getCount($service, $published = true)
	{
		$query = $service->createQuery();
		if ($published)
		{
			$query->add(Restrictions::published());
		}
		$result = $query->setProjection(Projections::rowCount('count'))->findUnique();
		return $result['count'];
	}

	/**
	 * @param DocumentService $service
	 * @param boolean $published
	 * @return integer
	 */
	private function getTodayCount($service, $created = true)
	{
		$query = $service->createQuery();
		$query->add(Restrictions::like($created ? 'creationdate' : 'modificationdate', date('Y-m-d'), MatchMode::START()));
		$result = $query->setProjection(Projections::rowCount('count'))->findUnique();
		return $result['count'];
	}
}