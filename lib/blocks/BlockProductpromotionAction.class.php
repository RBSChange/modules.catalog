<?php
/**
 * catalog_BlockProductpromotionAction
 * @package modules.catalog.lib.blocks
 */
class catalog_BlockProductpromotionAction extends catalog_BlockProductlistBaseAction
{
	/**
	 * @see website_BlockAction::execute()
	 *
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	function execute($request, $response)
	{
		$label = $this->getConfiguration()->getLabel();
		
		$request->setAttribute('blockTitle', 
				f_util_StringUtils::isEmpty($label) ? f_Locale::translate(
						'&modules.catalog.frontoffice.productpromotion-label;') : f_util_HtmlUtils::textToHtml(
						$label));
						
		if ($this->getConfiguration()->getMode() === "random")
		{
			$request->setAttribute('shop', catalog_ShopService::getInstance()->getCurrentShop());
			$productsIds = $this->getProductIdArray($request);
			if ($productsIds === null)
			{
				return website_BlockView::NONE;
			}
			$productsId = f_util_ArrayUtils::randomElement($productsIds);
			$request->setAttribute('product', DocumentHelper::getDocumentInstance($productsId));
			return 'Random';
		}
		else
		{
			return parent::execute($request, $response);
		}
	}
	
	/**
	 * @param f_mvc_Response $response
	 * @return catalog_persistentdocument_product[] or null
	 */
	protected function getProductArray($request)
	{
		$productsIds = $this->getConfiguration()
			->getProductsIds();
		if (f_util_ArrayUtils::isEmpty($productsIds))
		{
			return null;
		}
		
		$query = catalog_ProductService::getInstance()->createQuery()
			->add(Restrictions::published())
			->add(Restrictions::in('id', $productsIds));
		
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		$query->createCriteria('compiledproduct')
			->add(Restrictions::published())
			->add(Restrictions::eq('shopId', $shop->getId()));
		
		$result = $query->find();
		return count($result) ? $result : null;
	}
	
	/**
	 * @param f_mvc_Response $response
	 * @return integer[] or null
	 */
	protected function getProductIdArray($request)
	{
		
		$productsIds = $this->getConfiguration()
			->getProductsIds();
		if (f_util_ArrayUtils::isEmpty($productsIds))
		{
			return null;
		}
		
		$query = catalog_ProductService::getInstance()->createQuery()
			->add(Restrictions::published())
			->add(Restrictions::in('id', $productsIds))
			->setProjection(Projections::property('id'));
		
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		$query->createCriteria('compiledproduct')
			->add(Restrictions::published())
			->add(Restrictions::eq('shopId', $shop->getId()));
		
		$result = $query->findColumn('id');
		return count($result) ? $result : null;
	}
}