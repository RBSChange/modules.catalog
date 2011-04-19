<?php
class catalog_BlockDashboardstockAction extends dashboard_BlockDashboardAction
{	
	/**
	 * @return string
	 */
	public function getTitle()
	{
		return LocaleService::getInstance()->transBO('m.catalog.bo.blocks.dashboardstock.title');
	}

	/**
	 * @param f_mvc_Request $request
	 * @param boolean $forEdition
	 */
	protected function setRequestContent($request, $forEdition)
	{
		if ($forEdition)
		{
			return;
		}
		
		$products = $this->getUnavailableProducts();
		$productsCount = count($products);
		
		$ls = LocaleService::getInstance();
		$widget = array();
		if ($productsCount > 0)
		{
			$widget['columns'] = array(
				array('label' => $ls->transBO('m.catalog.document.product.label', array('ucf', 'html')), 'width' => '70%'),
				array('label' => $ls->transBO('m.catalog.document.product.codereference', array('ucf', 'html')), 'width' => '20%'),
				array('label' => $ls->transBO('m.catalog.document.product.stockquantity', array('ucf', 'html')), 'width' => '10%')
			);
			$widget['lines'] = array();
			$stSrv = catalog_StockService::getInstance();
			foreach ($products as $product)
			{
				$widget['lines'][] = array(
					'label' => $product->getLabelAsHtml(), 
					'id' => $product->getId(),
					'model' => str_replace('/', '_', $product->getDocumentModelName()),
					'reference' => $product->getCodeReferenceAsHtml(), 
					'quantity' => $stSrv->getStockableDocument($product)->getCurrentStockQuantity()
				);
			}
			$widget['header'] = $ls->transBO('m.catalog.bo.dashboard.out-of-stock-products', array('ucf', 'html'))." : ".$productsCount;
		}
		else
		{
			$widget['header'] = $ls->transBO('m.catalog.bo.dashboard.no-out-of-stock-products', array('ucf', 'html'));
		}
		$request->setAttribute('widget', $widget);
	}
	
	private $products = null;
	
	private function getUnavailableProducts()
	{
		if ($this->products === null)
		{
			$this->products = catalog_StockService::getInstance()->getOutOfStockProducts();			
		}
		return $this->products;
	}
}