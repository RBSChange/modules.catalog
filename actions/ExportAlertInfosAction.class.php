<?php
/**
 * catalog_ExportAlertInfosCsvAction
 * @package modules.catalog.actions
 */
class catalog_ExportAlertInfosAction extends change_Action
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$product = $this->getDocumentInstanceFromRequest($request);
		$request->setAttribute('product', $product);
		$request->setAttribute('alerts', catalog_AlertService::getInstance()->getPublishedByProduct($product));

		// Determine output type.
		if ($request->hasParameter('output'))
		{
			$output = ucfirst(strtolower($request->getParameter('output')));
		}
		if (empty($output))
		{
			$output = 'Csv';
		}
		
		// Check that the view exists. 
		$className = 'catalog_ExportAlertInfos' . $output . 'View';
		if (!f_util_ClassUtils::classExists($className))
		{
			throw new Exception('Unable to format output as "'.$output.'".');
		}

		return $output;
	}
}