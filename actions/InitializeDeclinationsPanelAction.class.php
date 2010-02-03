<?php
class catalog_InitializeDeclinationsPanelAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$product = $this->getDocumentInstanceFromRequest($request);
		
		$data = array();
		// TODO.
		return $this->sendJSON($data);
	}
}