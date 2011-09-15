<?php
/**
 * catalog_PreviewImageAction
 * @package modules.catalog.actions
 */
class catalog_PreviewImageAction extends generic_PreviewImageAction
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		try 
		{
			$doc = $this->getDocumentInstanceFromRequest($request);
			if (f_util_ClassUtils::methodExists($doc, 'getDefaultVisual'))
			{
				$visual = $doc->getDefaultVisual();
				if ($visual)
				{
					$request->setParameter('cmpref', $visual->getId());
					return $context->getController()->forward('media', 'Display');
				}
			}
		}
		catch (Exception $e)
		{
			// The document doesn't exist!
		}
		return parent::_execute($context, $request);
	}
}