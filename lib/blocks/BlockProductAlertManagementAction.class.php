<?php
/**
 * catalog_BlockProductAlertManagementAction
 * @package modules.catalog.lib.blocks
 */
class catalog_BlockProductAlertManagementAction extends website_BlockAction
{
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return string
	 */
	public function execute($request, $response)
	{
		if ($this->isInBackoffice())
		{
			return website_BlockView::NONE;
		}
		
		$currentUser = users_UserService::getInstance()->getCurrentFrontEndUser();
		if ($currentUser !== null)
		{
			$nbItemPerPage = $this->getConfiguration()->getNbItemPerPage();
			$alerts = catalog_AlertService::getInstance()->getPublishedByUser($currentUser);
			if (count($alerts) > 0)
			{
				$pageNumber = $request->getParameter('page');
				if (!is_numeric($pageNumber) || $pageNumber < 1 || $pageNumber > ceil(count($alerts) / $nbItemPerPage))
				{
					$pageNumber = 1;
				}
				$paginator = new paginator_Paginator('catalog', $pageNumber, $alerts, $nbItemPerPage);			
				$request->setAttribute('paginator', $paginator);
			}
			return 'List';
		}
		else
		{
			return 'Login';
		}
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return string
	 */
	public function executeRemoveAlert($request, $response)
	{
		try
		{
			$alert = DocumentHelper::getDocumentInstance($request->getParameter('alertId'));
		}
		catch (Exception $e)
		{
			if (Framework::isDebugEnabled())
			{
				Framework::exception($e);
			}
			$this->addError(LocaleService::getInstance()->trans('m.catalog.frontoffice.unexisting-alert', array('ucf')));
			return $this->getSuccessView($request);
		}
		
		if ($this->checkAccess($request, $alert))
		{
			$alert->delete();
			$this->addMessage(LocaleService::getInstance()->trans('m.catalog.frontoffice.alert-removed', array('ucf')));
		}
		else
		{
			$this->addError(LocaleService::getInstance()->trans('m.catalog.frontoffice.not-your-alert', array('ucf')));
		}
		return $this->getSuccessView($request);
	}
	
	/**
	 * @return string
	 */
	private function getSuccessView($request)
	{
		$request->setAttribute('currentUser', users_UserService::getInstance()->getCurrentFrontEndUser());
		return website_BlockView::SUCCESS;
	}	
	
	/**
	 * @param f_mvc_Request $request
	 * @param catalog_persistentdocument_alert $alert
	 */
	private function checkAccess($request, $alert)
	{
		if ($alert === null)
		{
			return false;
		}
		
		$password = $alert->getPassword();
		if ($password !== null && $password === $request->getParameter('password'))
		{
			return true;
		}
		
		$currentUser = users_UserService::getInstance()->getCurrentFrontEndUser();
		if ($currentUser !== null && $currentUser->getId() == $alert->getUserId())
		{
			return true;
		}

		return false;
	}
}
