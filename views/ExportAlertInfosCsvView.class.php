<?php
class catalog_ExportAlertInfosCsvView extends change_View
{
	public function _execute($context, $request)
	{
		$product = $request->getAttribute('product');
		
		$fieldNames = array(
			'email' => LocaleService::getInstance()->trans('m.catalog.document.alert.email', array('ucf')),
			'hasUser' => LocaleService::getInstance()->trans('m.catalog.document.alert.hasuser', array('ucf')),
			'alertType' => LocaleService::getInstance()->trans('m.catalog.document.alert.alerttype', array('ucf'))
		);
		
		$data = array();
		foreach ($request->getAttribute('alerts') as $alert)
		{
			$data[] = array(
				'email' => $alert->getEmail(),
				'hasUser' => ($alert->hasUser() ? 1 : 0),
				'alertType' => $alert->getAlertType(),
			);
		}

		$fileName = 'export_alertsForProduct_'.f_util_FileUtils::cleanFilename($product->getLabel()).'_'.date('Ymd_His').'.csv';
		$options = new f_util_CSVUtils_export_options();
		$options->separator = ';';
		
		$csv = f_util_CSVUtils::export($fieldNames, $data, $options);		
		header('Content-type: text/comma-separated-values');
		header('Content-length: '.strlen($csv));
		header('Content-disposition: attachment; filename="'.$fileName.'"');
		echo $csv;
		exit;
	}
}