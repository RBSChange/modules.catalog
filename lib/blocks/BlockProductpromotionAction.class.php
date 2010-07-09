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
		$productsIds = $this->getConfiguration()->getProductsIds();
		if (f_util_ArrayUtils::isEmpty($productsIds))
		{
			return website_BlockView::NONE;
		}
		$shop =  catalog_ShopService::getInstance()->getCurrentShop();
		$products = $this->getProductsByIds($productsIds, $shop->getId());
		if (count($products) == 0)
		{
			return website_BlockView::NONE;
		}		
		
		$label = $this->getConfiguration()->getLabel();	
		$request->setAttribute('shop', $shop);
		$request->setAttribute('blockTitle', f_util_StringUtils::isEmpty($label) ? f_Locale::translate('&modules.catalog.frontoffice.productpromotion-label;') : $label);
		if ($this->getConfiguration()->getMode() === "random")
		{
			$request->setAttribute('product', f_util_ArrayUtils::randomElement($products));
			return 'Random';
		}
		else 
		{
			$displayConfig = $this->getDisplayConfig($shop);
			$request->setAttribute('displayConfig', $displayConfig);
			$request->setAttribute('products', $products);
			$request->setAttribute('blockView', $this->getConfiguration()->getDisplayMode());
			return $this->forward('catalog', 'productlist');
		}
	}
	
	/**
	 * @param integer[] $productsIds
	 * @param integer $shopId
	 * @return catalog_persistentdocument_product[]
	 */
	private function getProductsByIds($productsIds, $shopId)
	{
		$query = catalog_ProductService::getInstance()->createQuery()
					->add(Restrictions::published())
					->add(Restrictions::in('id', $productsIds));
		
		$query->createCriteria('compiledproduct')
					->add(Restrictions::published())
					->add(Restrictions::eq('shopId', $shopId));
		
		return $query->find();	
	}
}