<?php
/**
 * catalog_BlockBundleproductAction
 * @package modules.catalog.lib.blocks
 */
class catalog_BlockBundleproductAction extends catalog_BlockProductBaseAction
{
	public function getCacheKeyParameters($request)
	{
		return array("bundleditemid" => $request->getParameter("bundleditemid"));
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	public function execute($request, $response)
	{
		$product = $this->getDocumentParameter();
		
		$shop = catalog_ShopService::getInstance()->getCurrentShop(); 
		
		$customer = null;
		if (catalog_ModuleService::areCustomersEnabled())
		{
			$customer = customer_CustomerService::getInstance()->getCurrentCustomer();
		}
		
		$prices = $product->getPrices($shop, $customer);
		$price = array_shift($prices);
		
		$request->setAttribute('product', $product);
		$request->setAttribute('defaultPrice', $price);
		$request->setAttribute('differencePrice', $product->getPriceDifference($shop, $customer));
		$request->setAttribute('thresholdPrices', $prices);
		
		if ($request->hasParameter('bundleditemid'))
		{
			$bundleditem = DocumentHelper::getDocumentInstance($request->getParameter('bundleditemid'), 'modules_catalog/bundleditem');
			$request->setAttribute('bundleditem', $bundleditem);
			$request->setAttribute('bundledproduct', $bundleditem->getProduct());
			return 'Bundleditem';
		}
		
		return website_BlockView::SUCCESS;
	}
}