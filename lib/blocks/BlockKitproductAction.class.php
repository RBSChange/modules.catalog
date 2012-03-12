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
	
	function getCacheKeyParameters($request)
	{
		return array('kititemid' => $request->getParameter('kititemid'),
			'declinationid' => $request->getParameter('declinationid'),
			'customitems' => $request->getParameter('customitems'));
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
		
		// @deprecated this should not be used anymore. See catalog_AddToCartAction
		if ($request->getParameter('addToCart') !== null)
		{
			$this->addProductToCartForCurrentBlock($product);
		}
		
		// @deprecated this should not be used anymore. See catalog_AddToListAction
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
		if ($request->hasParameter('kititemid'))
		{
			$kititem = catalog_persistentdocument_kititem::getInstanceById($request->getParameter('kititemid'));
			$request->setAttribute('kititem', $kititem);
			if ($kititem->getDeclinable() && $request->hasParameter('declinationid'))
			{
				$customProduct = catalog_persistentdocument_product::getInstanceById($request->getParameter('declinationid'));
				foreach ($kititem->getProduct()->getDeclinations() as $declination)
				{
					if ($customProduct === $declination)
					{
						$kititem->setCurrentProduct($customProduct);
					}
				}
			}
			
			if ($kititem->getDeclinable()) 
			{
				if ($kititem->getCurrentProduct() == null)
				{
					$kititem->setDefaultProductForShop($shop);
				}
				$declination = $kititem->getCurrentProduct();
				$request->setAttribute('declination', $declination);
				$request->setAttribute('kititemproduct', $declination);
			}
			else
			{
				$request->setAttribute('kititemproduct', $kititem->getProduct());
			}
			$request->setAttribute('customitems', $kis->getCustomItemsInfo($product));
			return 'Kititem';
		}
		$billingArea = $shop->getCurrentBillingArea();
		$price = $product->getPrice($shop, $billingArea, $customer);
		if ($price)
		{
			$request->setAttribute('defaultPrice', $price);
			$request->setAttribute('differencePrice', $product->getPriceDifference($shop, $billingArea, $customer));
		}
		$request->setAttribute('customitems', $kis->getCustomItemsInfo($product));
		return website_BlockView::SUCCESS;
	}
}
