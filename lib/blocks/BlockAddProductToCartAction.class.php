<?php
/**
 * @method catalog_BlockAddProductToCartConfiguration getConfiguration()
 */
class catalog_BlockAddProductToCartAction extends website_BlockAction
{
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return string
	 */
	public function execute($request, $response)
	{
		if ($this->isInBackofficeEdition()) {return null;}
		
		$product = DocumentHelper::getDocumentInstanceIfExists($this->getDocumentIdParameter());
		if (($product instanceof catalog_persistentdocument_product) && $product->isPublished())
		{
			$request->setAttribute('product', $product);
			$productStock = catalog_StockService::getInstance()->getStockableDocument($product);
			$request->setAttribute('productStock', $productStock);
		}
		else
		{
			return null;
		}
		
		$shop = $this->getShop();
		$request->setAttribute('shop', $shop);
		if ($request->hasParameter('defaultPrice'))
		{
			$price = $request->getParameter('defaultPrice');
		}
		else
		{
			$customer = customer_CustomerService::getInstance()->getCurrentCustomer();
			$price = $product->getPrice($shop, $shop->getCurrentBillingArea(), $customer, $product->getMinOrderQuantity());
		}
				
		$canBeAddedToCart = $product->canBeAddedToCart($price);
		$request->setAttribute('canBeAddedToCart', $canBeAddedToCart);
		$request->setAttribute('addToCartURL', $canBeAddedToCart ? $product->getAddToCartUrl() : '');
		
		$diplayMode = $this->getConfiguration()->getDisplayMode();
		if ($diplayMode === 'short')
		{
			return 'Short';
		}
		return 'Success';
	}
	
	/**
	 * 
	 * @return catalog_persistentdocument_shop
	 */
	protected function getShop()
	{
		$shop = $this->getConfiguration()->getShopSafe();
		return ($shop instanceof catalog_persistentdocument_shop) ? $shop : catalog_ShopService::getInstance()->getCurrentShop();
	}
}