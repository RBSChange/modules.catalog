<?php
/**
 * catalog_GetLinkedProductsToSortAction
 * @package modules.catalog.actions
 */
class catalog_GetLinkedProductsToSortAction extends change_JSONAction
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$result = array();

		$target = DocumentHelper::getDocumentInstance($request->getParameter('targetId'));
		$linkType = $request->getParameter('linkType');

		$result['nodes'] = catalog_CompiledcrossitemService::getInstance()->getSortingInfosByTargetAndLinkType($target, $linkType);

		return $this->sendJSON($result);
	}
}