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
	 * @see website_BlockAction::execute()
	 *
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	function execute($request, $response)
	{
		$product = $this->getCurrentKit();
		$kis = $product->getDocumentService();
		$kis->updateProductFromRequestParameters($product, $request->getParameters());
		$shop = catalog_ShopService::getInstance()->getCurrentShop(); 
		
		// Add to product list if needed.
		if ($request->getParameter('addToList') !== null)
		{
			$this->addProductToFavorites($product);
		}
		
		$customer = null;
		if (catalog_ModuleService::areCustomersEnabled())
		{
			$customer = customer_CustomerService::getInstance()->getCurrentCustomer();
		}
		
		$request->setAttribute('product', $product);
		
		$price = $product->getPrice($shop, $customer);
		$request->setAttribute('defaultPrice', $price);
		$request->setAttribute('differencePrice', $product->getPriceDifference($shop, $customer));
		
		if ($request->hasParameter('kititemid'))
		{
			$kititem = DocumentHelper::getDocumentInstance($request->getParameter('kititemid'), 'modules_catalog/kititem');
			$request->setAttribute('kititem', $kititem);
			if ($request->hasParameter('declinationid'))
			{
				$declination = DocumentHelper::getDocumentInstance($request->getParameter('declinationid'), 'modules_catalog/productdeclination');
				$kititem->setCurrentProduct($declination);
			}
			
			if ($kititem->getProduct() instanceof catalog_persistentdocument_declinedproduct) 
			{
				if ($kititem->getCurrentProduct())
				{
					$declination = $kititem->getCurrentProduct();
				}
				else
				{
					$declination = $kititem->getProduct()->getDefaultDeclination($shop);
				}
				
				$request->setAttribute('declination', $declination);
			}
			
			$request->setAttribute('customitems', $kis->getCustomItemsInfo($product));
			$request->setAttribute('kititemproduct', $kititem->getProduct());
			return 'Kititem';
		}
		
		$request->setAttribute('customitems', $kis->getCustomItemsInfo($product));
		return website_BlockView::SUCCESS;
	}
}