<?php
/**
 * catalog_BlockBundleListByProductAction
 * @package modules.catalog.lib.blocks
 */
class catalog_BlockBundleListByProductAction extends website_BlockAction
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
		if ($this->isInBackoffice())
		{
			return website_BlockView::NONE;
		}
		
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
    	$product = $this->getDocumentParameter();    	
	    if ($product === null || !($product instanceof catalog_persistentdocument_product) || !$product->isPublished())
	    {
	    	if ($product !== null)
	    	{
	    		Framework::error(__METHOD__ . ' Invalid product type');
	    	}
	    	return website_BlockView::NONE;
	    }
	    
	    $bundleProducts = catalog_BundleproductService::getInstance()->getByBundledProduct($shop, $product);
	    if (count($bundleProducts) > 0)
	    {
	    	
	    	$request->setAttribute('shop', $shop);
	    	
			$customer = null;
			if (catalog_ModuleService::areCustomersEnabled())
			{
				$customer = customer_CustomerService::getInstance()->getCurrentCustomer();
			}
			$request->setAttribute('customer', $customer);
			
	    	$request->setAttribute('bundleProducts', $bundleProducts);
			return website_BlockView::SUCCESS;
	    }
	    return website_BlockView::NONE;
	}
}