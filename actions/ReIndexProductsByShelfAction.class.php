<?php
/**
 * catalog_ReIndexProductsByShelfAction
 * @package modules.catalog.actions
 */
class catalog_ReIndexProductsByShelfAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$result = array();
		
		$shelf = $this->getDocumentInstanceFromRequest($request);
		catalog_CompiledproductService::getInstance()->reIndexByShelf($shelf);
		
		return $this->sendJSON($result);
	}
}