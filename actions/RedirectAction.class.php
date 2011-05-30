<?php
/**
 * catalog_RedirectAction
 * @author intportg
 */
class catalog_RedirectAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param ChangeRequest $request
	 */
	public function _execute($context, $request)
    {
		$subRef = DocumentHelper::getDocumentInstance($request->getParameter('subRef'));
		if ($subRef instanceof catalog_persistentdocument_shelf && $request->hasParameter('parentTopic')) 
		{
			 $sytemTopic = website_SystemtopicService::getInstance()->createQuery()
					->add(Restrictions::descendentOf($request->getParameter('parentTopic')))
					->add(Restrictions::eq('referenceId', $subRef->getId()))
					->findUnique();
					
			if ($sytemTopic)
			{
				$subRef = $sytemTopic;
			}
		}
		header('Location: ' . LinkHelper::getDocumentUrl($subRef));
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