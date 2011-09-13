<?php
/**
 * catalog_PreviewImageAction
 * @package modules.catalog.actions
 */
class catalog_PreviewImageAction extends generic_PreviewImageAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		try 
		{
			$product = catalog_persistentdocument_product::getInstanceById($this->getDocumentIdFromRequest($request));
			$visual = $product->getDefaultVisual();
			if (!$visual)
			{
				return parent::_execute($context, $request);
			}
			$request->setParameter('cmpref', $visual->getId());
			return $context->getController()->forward('media', 'Display');
		}
		catch (Exception $e)
		{
			return parent::_execute($context, $request);
		}
	}
}