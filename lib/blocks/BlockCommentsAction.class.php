<?php
/**
 * catalog_BlockCommentsAction
 * @package modules.catalog.lib.blocks
 */
class catalog_BlockCommentsAction extends comment_BlockCommentsBaseAction
{
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return string
	 */
	public function execute($request, $response)
	{
		if (!catalog_ModuleService::getInstance()->areCommentsEnabled())
		{
			return website_BlockView::NONE;
		}
		return parent::execute($request, $response);
	}
}