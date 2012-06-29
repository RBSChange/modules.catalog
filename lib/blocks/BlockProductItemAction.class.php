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
		$keys = array('displayConfig' => $displayConfig,
			'productid' => $this->getDocumentIdParameter(),
			'shopId' => $this->findParameterValue('shop')->getId(),
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
		Framework::info(__METHOD__ . ' -> ' . $this->getDocumentIdParameter());
		
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
			$popinPageId = $compiledProduct->getPopinPageId();
			if ($popinPageId)
			{
				$request->setAttribute('popinPageId', $popinPageId);
			}
		}
		return ucfirst($request->getParameter('displayMode', 'list'));
	}
}