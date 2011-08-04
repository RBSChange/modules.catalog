<?php
/**
 * catalog_RedirectAction
 * @author intportg
 */
class catalog_RedirectAction extends change_Action
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
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
        return change_View::NONE ;
    }

    public function getRequestMethods()
	{
		return change_Request::POST;
	}

	public function isSecure()
	{
		return false;
	}
}