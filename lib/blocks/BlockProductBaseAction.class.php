<?php
/**
 * catalog_BlockProductBaseAction
 * @package modules.catalog
 */
abstract class catalog_BlockProductBaseAction extends website_BlockAction
{
	public function initialize($request)
	{
		$request->setAttribute("formBlockId", $this->getBlockId());
	}
	
	/**
	 * @return boolean
	 */
	public function isCacheEnabled()
	{
		// Disable cache if old addToXXX parameters are used
		$request = $this->getRequest();
		return parent::isCacheEnabled() && !$request->hasParameter("addToCart")
			&& !$request->hasParameter("addToList");
	}
}