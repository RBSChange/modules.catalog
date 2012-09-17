<?php
/**
 * catalog_BlockProductItemAction
 * @package modules.catalog
 */
class catalog_BlockProductItemAction extends website_BlockAction
{
	/**
	 * @param website_BlockActionRequest $request
	 * @return array
	 */
	public function getCacheKeyParameters($request)
	{
		$displayConfig = $request->getParameter('displayConfig');
		$shop = $this->findParameterValue('shop');
		$shopId = $ba = 0;
		if ($shop instanceof catalog_persistentdocument_shop)
		{
			$shopId = $shop->getId();
			$ba = $shop->getCurrentBillingArea()->getId();
		}
		$keys = array('displayConfig' => $displayConfig,
			'productid' => $this->getDocumentIdParameter(),
			'shopId' => $shopId, 'ba' => $ba,
			'displayMode' => $request->getParameter('displayMode', 'list'));
		
		
		if (isset($displayConfig['displayCustomerPrice']) && $displayConfig['displayCustomerPrice'])
		{
			$customer = customer_CustomerService::getInstance()->getCurrentCustomer();
			if ($customer)
			{
				$keys['customerid'] = $customer->getId();
			}
		}
		return $keys;
	}

	/**
	 * @return array
	 */
	public function getCacheDependencies()
	{
		$product = $this->getDocumentParameter();
		$deps = array($product->getId());
		$visual = $product->getDefaultVisual($this->findParameterValue('shop'));
		if ($visual)
		{
			$deps[] = $visual->getId();
		}
		
		$displayConfig = $this->findParameterValue('displayConfig');
		if (isset($displayConfig['displayCustomerPrice']) && $displayConfig['displayCustomerPrice'])
		{
			$customer = customer_CustomerService::getInstance()->getCurrentCustomer();
			if ($customer)
			{
				$deps[] = $customer->getId();
			}
		}
		return $deps;
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return string
	 */
	public function execute($request, $response)
	{		
		$displayConfig = $request->getParameter('displayConfig', array());
		if (isset($displayConfig['displayCustomerPrice']) && $displayConfig['displayCustomerPrice'])
		{
			$request->setAttribute('customer', customer_CustomerService::getInstance()->getCurrentCustomer());
		}
		else
		{
			$request->setAttribute('customer', null);
		}
		
		/* @var $product catalog_persistentdocument_product */
		$product = $this->getDocumentParameter();
		$request->setAttribute('product', $product);
		
		if ($displayConfig['useAddToCartPopin'])
		{
			$shop = $this->findParameterValue('shop');
			$compiledProduct = $product->getDocumentService()->getPrimaryCompiledProductForShop($product, $shop);
			$popinPageId = $compiledProduct ? $compiledProduct->getPopinPageId() : null;
			if ($popinPageId)
			{
				$request->setAttribute('popinPageId', $popinPageId);
			}
		}
		return ucfirst($request->getParameter('displayMode', 'list'));
	}
}