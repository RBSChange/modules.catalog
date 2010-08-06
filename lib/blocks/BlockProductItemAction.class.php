<?php
/**
 * catalog_BlockProductItemAction
 * @package modules.catalog
 */
class catalog_BlockProductItemAction extends website_BlockAction
{
	/* (non-PHPdoc)
	 * @see modules/website/lib/mvc/website_BlockAction::getCacheKeyParameters()
	 */
	public function getCacheKeyParameters($request)
	{
		$displayConfig = $request->getParameter('displayConfig');	
		$keys = array('displayConfig' => $displayConfig,
			'productid' => $this->getDocumentIdParameter(),
			'lang' => RequestContext::getInstance()->getLang(),
			'shopId' => $this->findParameterValue('shop')->getId(),
			'displayMode' => $request->getParameter('displayMode', 'list'));
		
		if ($displayConfig['displayCustomerPrice'])
		{
			$customer = customer_CustomerService::getInstance()->getCurrentCustomer();
			if ($customer)
			{
				$keys['customerid'] = $customer->getId();
			}
		}
		
		return $keys;
	}

	/* (non-PHPdoc)
	 * @see framework/f_mvc/f_mvc_Action::getCacheDependencies()
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
		if ($displayConfig['displayCustomerPrice'])
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
	 * @return String
	 */
	public function execute($request, $response)
	{
		Framework::info(__METHOD__ . ' -> ' . $this->getDocumentIdParameter());
		
		$displayConfig = $request->getParameter('displayConfig', array());
		if ($displayConfig['displayCustomerPrice'])
		{
			$request->setAttribute('customer', customer_CustomerService::getInstance()->getCurrentCustomer());
		}
		else
		{
			$request->setAttribute('customer', null);
		}
		$request->setAttribute('product', $this->getDocumentParameter());
		return ucfirst($request->getParameter('displayMode', 'list'));
	}
}