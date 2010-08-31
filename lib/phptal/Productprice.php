<?php
// change:productprice
//
// <tal:block change:productprice="name 'price'; product product">
// <tal:block change:productprice="name 'price'; product product; shop shop; customer customer">
// <tal:block change:productprice="name 'price'; product product; shop shop; customer 'null'">

/**
 * @package catalog.lib.phptal
 */
class PHPTAL_Php_Attribute_CHANGE_productprice extends ChangeTalAttribute 
{
	/**
	 * @see ChangeTalAttribute::getEvaluatedParameters()
	 * @return array
	 */
	public function getEvaluatedParameters()
	{
		return array('name', 'product', 'shop', 'customer');
	}
	
	/**
	 * @see ChangeTalAttribute::start()
	 */
	public function start()
	{
		$this->initParams();
		$shopCode = $this->hasParameter('shop') ? $this->getParameter('shop') : 'catalog_ShopService::getInstance()->getCurrentShop()';
		$customerCode = $this->hasParameter('customer') ? ('(' . $this->getParameter('customer') . ' == "null" ? null : ' . $this->getParameter('customer') . ')') : 'customer_CustomerService::getInstance()->getCurrentCustomer()';
		$code = $this->getParameter('product') . '->getPrice(' . $shopCode . ', ' . $customerCode . ');';
		$this->tag->generator->doSetVar('$ctx->{' . $this->getParameter('name') . '}', $code);
	}
}