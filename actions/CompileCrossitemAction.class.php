<?php
/**
 * catalog_CompileCrossitemAction
 * @package modules.catalog.actions
 */
class catalog_CompileCrossitemAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$result = array();

		$crossitem = catalog_persistentdocument_crossitem::getInstanceById($request->getParameter('cmpref'));
		catalog_CompiledcrossitemService::getInstance()->generateForCrossitem($crossitem);

		return $this->sendJSON($result);
	}
}