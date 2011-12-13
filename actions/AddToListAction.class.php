<?php
/**
 * @deprecated use catalog/UpdateList action
 */
class catalog_AddToListAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		// Get parameters.
		$product = $this->getProductFromRequest($request);
		$backUrl = $request->getParameter('backurl');
		$listName = $request->getParameter('listName', catalog_ProductList::FAVORITE);
		
		// Configurate the product
		$product->getDocumentService()->updateProductFromRequestParameters($product, $request->getParameters());
		
		if (catalog_ModuleService::getInstance()->addProductIdToList($listName, $product->getId()))
		{
			website_SessionMessage::addVolatileMessage(LocaleService::getInstance()->transFO('m.catalog.frontoffice.product-added-to-list', array('ucf')));
		}
		else
		{
			website_SessionMessage::addVolatileError(LocaleService::getInstance()->transFO('m.catalog.frontoffice.product-already-in-list', array('ucf')));
		}
		
		// Redirect.
		if (!$backUrl)
		{
			$tag = 'contextual_website_website_modules_catalog_'.$listName.'-product-list';
			$website = website_WebsiteModuleService::getInstance()->getCurrentWebsite();
			$doc = TagService::getInstance()->getDocumentByContextualTag($tag, $website, false);
			if ($doc === null)
			{
				$backUrl = LinkHelper::getDocumentUrl($website);		
			}
			else
			{
				$backUrl = LinkHelper::getDocumentUrlForWebsite($doc, $website);
			}
		}
		HttpController::getInstance()->redirectToUrl($backUrl);
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @return catalog_persistentdocument_product
	 */
	protected function getProductFromRequest($request)
	{
		if ($request->hasParameter('productId'))
		{
			return catalog_persistentdocument_product::getInstanceById($request->getParameter('productId'));
		}
		return null;
	}
	
	/**
	 * @return boolean
	 */
	public function isSecure()
	{
		return false;
	}
}