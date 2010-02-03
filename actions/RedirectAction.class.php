<?php
/**
 * catalog_RedirectAction
 * @author intportg
 */
class catalog_RedirectAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
    {
		$subRef = DocumentHelper::getDocumentInstance($context->getRequest()->getParameter('subRef'));
		header('Location: ' . str_replace('&amp;', '&', LinkHelper::getUrl($subRef)));
        return View::NONE ;
    }

    public function getRequestMethods()
	{
		return Request::POST;
	}

	public function isSecure()
	{
		return false;
	}
}