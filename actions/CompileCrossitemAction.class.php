<?php
/**
 * catalog_CompileCrossitemAction
 * @package modules.catalog.actions
 */
class catalog_CompileCrossitemAction extends change_JSONAction
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$result = array();

		$crossitem = catalog_persistentdocument_crossitem::getInstanceById($request->getParameter('cmpref'));
		catalog_CompiledcrossitemService::getInstance()->generateForCrossitem($crossitem);

		return $this->sendJSON($result);
	}
}