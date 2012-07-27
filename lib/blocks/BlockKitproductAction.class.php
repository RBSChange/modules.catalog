<?php
/**
 * catalog_BlockKitproductAction
 * @package modules.catalog.lib.blocks
 */
class catalog_BlockKitproductAction extends catalog_BlockProductBaseAction
{
	/**
	 * @return catalog_persistentdocument_kit
	 */
	private function getCurrentKit()
	{
		return $this->getDocumentParameter('cmpref', 'catalog_persistentdocument_kit'); 
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @return array
	 */
	public function getCacheKeyParameters($request)
	{
		return array('kititemid' => $request->getParameter('kititemid'));
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	public function execute($request, $response)
	{
		$product = $this->getCurrentKit();
		$kis = $product->getDocumentService();
		$kis->updateProductFromRequestParameters($product, $request->getParameters());
		$shop = catalog_ShopService::getInstance()->getCurrentShop(); 
		
		// @deprecated this should not be used anymore. See order_AddToCartAction
		if ($request->getParameter('addToCart') !== null)
		{
			$this->addProductToCartForCurrentBlock($product);
		}
		
		// @deprecated this should not be used anymore. See catalog_UpdateListAction
		if ($request->getParameter('addToList') !== null)
		{
			$this->addProductToFavorites($product);
		}
		
		$customer = null;
		if (catalog_ModuleService::getInstance()->areCustomersEnabled())
		{
			$customer = customer_CustomerService::getInstance()->getCurrentCustomer();
		}
		
		$request->setAttribute('product', $product);
		if ($request->hasParameter('kititemid'))
		{
			$kititem = catalog_persistentdocument_kititem::getInstanceById($request->getParameter('kititemid'));
			$request->setAttribute('kititem', $kititem);
			$request->setAttribute('kititemproduct', $kititem->getDefaultProduct());
			return 'Kititem';
		}
		$billingArea = $shop->getCurrentBillingArea();
		$price = $product->getPrice($shop, $billingArea, $customer);
		if ($price)
		{
			$request->setAttribute('defaultPrice', $price);
			$request->setAttribute('differencePrice', $product->getPriceDifference($shop, $billingArea, $customer));
		}
		return website_BlockView::SUCCESS;
	}
}