<?php
/**
 * @package modules.catalog.lib.blocks
 */
class catalog_BlockLastConsultedProductsAction extends catalog_BlockProductlistBaseAction
{
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	public function execute($request, $response)
	{
		if ($this->isInBackofficeEdition())
		{
			return website_BlockView::NONE;
		}
		
		$label = $this->getConfiguration()->getLabel();
		
		$request->setAttribute('blockTitle', f_util_StringUtils::isEmpty($label) ? LocaleService::getInstance()->transFO('m.catalog.frontoffice.last-consulted-products', array('ucf')) : f_util_HtmlUtils::textToHtml($label));

		$mode = $this->getConfiguration()->getMode();
		if ($mode === "random" || $mode === "compact")
		{
			$request->setAttribute('shop', catalog_ShopService::getInstance()->getCurrentShop());
			$productsIds = $this->getProductIdArray($request);
			if ($productsIds === null)
			{
				return website_BlockView::NONE;
			}
			
			if ($mode === "random")
			{
				$productsId = f_util_ArrayUtils::randomElement($productsIds);
				$request->setAttribute('product', DocumentHelper::getDocumentInstance($productsId));
				return 'Random';
			}
			elseif ($mode === "compact")
			{
				$productsIds = array_slice($productsIds, 0, $this->getConfiguration()->getNbresultsperpage());
				$request->setAttribute('products', DocumentHelper::getDocumentArrayFromIdArray($productsIds));
				return 'Compact';
			}
		}
		else
		{
			return parent::execute($request, $response);
		}
	
		return website_BlockView::SUCCESS;
	}
	
	/**
	 * @param f_mvc_Response $response
	 * @return catalog_persistentdocument_product[] or null
	 */
	protected function getProductArray($request)
	{
		$productsIds = $this->getProductIdArray($request);
		if (f_util_ArrayUtils::isEmpty($productsIds))
		{
			return null;
		}
		$result = array();
		foreach ($productsIds as $id)
		{
			$result[] = DocumentHelper::getDocumentInstance($id, 'modules_catalog/product');
		}
		return $result;
	}
	
	/**
	 * @param f_mvc_Response $response
	 * @return integer[] or null
	 */
	protected function getProductIdArray($request)
	{
		$productsIds = catalog_ModuleService::getInstance()->getConsultedProductIds();
		if (f_util_ArrayUtils::isEmpty($productsIds))
		{
			return null;
		}
		
		$query = catalog_ProductService::getInstance()->createQuery()
					->add(Restrictions::published())
					->add(Restrictions::in('id', $productsIds))
					->setProjection(Projections::property('id'));
		
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		$query->createCriteria('compiledproduct')
				->add(Restrictions::published())
				->add(Restrictions::eq('shopId', $shop->getId()));
		
		$result = array_intersect($productsIds, $query->findColumn('id'));
		return count($result) ? $result : null;
	}
}