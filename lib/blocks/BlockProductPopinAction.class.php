<?php
/**
 * catalog_BlockProductPopinAction
 * @package modules.catalog.lib.blocks
 */
class catalog_BlockProductPopinAction extends catalog_BlockProductAction
{
	/**
	 * @param f_mvc_Request $request
	 * @param catalog_persistentdocument_shop $shop
	 */
	protected function setDisplayConfig($request, $shop)
	{
		parent::setDisplayConfig($request, $shop);
		$displayConfig = $request->getAttribute('displayConfig');
		$displayConfig['isPopin'] = true;
		$displayConfig['popinPageId'] = $this->getContext()->getId();
		$request->setAttribute('displayConfig', $displayConfig);
	}	
	
	/**
	 * @return boolean
	 */
	protected function isStandalone()
	{
		return true;
	}
}