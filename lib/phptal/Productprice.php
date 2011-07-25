<?php
// change:productprice
//
// <tal:block change:productprice="name 'price'; product product">
// <tal:block change:productprice="name 'price'; product product; shop shop; customer customer">
// <tal:block change:productprice="name 'price'; product product; shop shop; customer 'null'">

/**
 * @package catalog.lib.phptal
 */
class PHPTAL_Php_Attribute_CHANGE_Productprice extends ChangeTalAttribute 
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
     * Called before element printing.
     */
    public function before(PHPTAL_Php_CodeWriter $codewriter)
    {
		$this->initParams($codewriter);
		$shopCode = $this->hasParameter('shop') ? $this->getParameter('shop') : 'catalog_ShopService::getInstance()->getCurrentShop()';
		$customerCode = $this->hasParameter('customer') ? ('(' . $this->getParameter('customer') . ' == "null" ? null : ' . $this->getParameter('customer') . ')') : 'customer_CustomerService::getInstance()->getCurrentCustomer()';
		$code = $this->getParameter('product') . '->getPrice(' . $shopCode . ', ' . $customerCode . ');';
		$codewriter->doSetVar('$ctx->{' . $this->getParameter('name') . '}', $code);
	}
}