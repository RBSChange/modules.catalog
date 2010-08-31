<?php
// change:productvisual
//
// <tal:block change:productvisual="name 'visual'; product product">
// <tal:block change:productvisual="name 'visual'; mode 'detail|list'; product product; shop shop">

/**
 * @package catalog.lib.phptal
 */
class PHPTAL_Php_Attribute_CHANGE_productvisual extends ChangeTalAttribute 
{
	/**
	 * @see ChangeTalAttribute::getEvaluatedParameters()
	 * @return array
	 */
	public function getEvaluatedParameters()
	{
		return array('name', 'product', 'shop', 'mode');
	}
	
	/**
	 * @see ChangeTalAttribute::start()
	 */
	public function start()
	{
		$this->initParams();
		$method = $this->hasParameter('mode') ? ($this->getParameter('mode') . ' == "list" ? "getListVisual" : "getDefaultVisual"') : 'getDefaultVisual';
		$shopCode = $this->hasParameter('shop') ? $this->getParameter('shop') : 'catalog_ShopService::getInstance()->getCurrentShop()';
		$code = $this->getParameter('product') . '->{' . $method . '}(' . $shopCode . ');';
		$this->tag->generator->doSetVar('$ctx->{' . $this->getParameter('name') . '}', $code);
	}
}