<?php
/**
 * catalog_BlockProductCrossSellingAction
 * @package modules.catalog
 */
class catalog_BlockProductCrossSellingAction extends catalog_BlockProductlistBaseAction
{
	/**
	 * @param f_mvc_Request $request
	 * @return String
	 */
	protected function getDisplayMode($request)
	{
		return 'CrossSelling';
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	public function execute($request, $response)
	{
		if ($this->isInBackoffice())
		{
			return website_BlockView::NONE;
		}
		return parent::execute($request, $response);
	}
	
	/**
	 * @param f_mvc_Response $response
	 * @return catalog_persistentdocument_product[]
	 */
	protected function getProductArray($request)
	{
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
    	$product = $this->getDocumentParameter();
    	$request->setAttribute('globalProduct', $product);
    	
	    if ($product === null || !($product instanceof catalog_persistentdocument_product) || !$product->isPublished())
	    {
	    	if ($product !== null)
	    	{
	    		Framework::warn(__METHOD__ . ' Invalid product');
	    	}
	    	return array();
	    }
	    $request->setAttribute('product', $product);
	    
	    $configuration = $this->getConfiguration();
	    $sellingType = $configuration->getCrossSellingType();
	    
    	$relatedProducts = $product->getDisplayableCrossSelling($shop, $sellingType, $configuration->getSortby());
    	if (count($relatedProducts) == 0)
		{
			return array();	
		}
		
		$list = list_ListService::getInstance()->getByListId('modules_catalog/crosssellingtypes');
		$typeLabel = f_util_HtmlUtils::textToHtml($list->getItemByValue($sellingType)->getLabel());
		$request->setAttribute('blockTitle', $typeLabel);
		$request->setAttribute('typeLabel', f_util_StringUtils::lcfirst($typeLabel));
    	return array_slice($relatedProducts, 0, $configuration->getMaxdisplayed());
	}
}