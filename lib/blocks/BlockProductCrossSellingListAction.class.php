<?php
/**
 * catalog_BlockProductCrossSellingListAction
 * @package modules.catalog
 */
class catalog_BlockProductCrossSellingListAction extends catalog_BlockProductlistBaseAction
{	
	/**
	 * @param f_mvc_Response $response
	 * @return catalog_persistentdocument_product[]
	 */
	protected function getProductArray($request)
	{
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
    	$product = $this->getDocumentParameter();

		$productUrl = LinkHelper::getDocumentUrl($product);
		$replacements = array('productLink' => '<a class="link" href="'.$productUrl.'">'.$product->getLabelAsHtml().'</a>');
		$request->setAttribute('blockTitle', f_Locale::translate('&modules.catalog.frontoffice.Cross-selling-'.$request->getParameter('relationType').';', $replacements));
		return $product->getDisplayableCrossSelling($shop, $request->getParameter('relationType'));
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @return String
	 */
	protected function getDisplayMode($request)
	{
		return 'table';
	}
}