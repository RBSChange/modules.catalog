<?php
class catalog_BlockDashboardstockAction extends dashboard_BlockDashboardAction
{	
	/**
	 * @return string
	 */
	public function getTitle()
	{
		$products = $this->getUnavailableProducts();
		return f_Locale::translate('&modules.catalog.bo.blocks.Dashboardstock.title;').' ('.count($products).')';
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
		
		$widget = array();
		if ($productsCount > 0)
		{
			$widget['columns'] = array(
				array('label' => f_Locale::translate('&modules.catalog.document.product.Label;'), 'width' => '70%'),
				array('label' => f_Locale::translate('&modules.catalog.document.product.CodeReference;'), 'width' => '20%'),
				array('label' => f_Locale::translate('&modules.catalog.document.product.StockQuantity;'), 'width' => '10%')
			);
			$widget['lines'] = array();
			$stSrv = catalog_StockService::getInstance();
			foreach ($products as $product)
			{
				$widget['lines'][] = array(
					'label' => $product->getLabel(), 
					'id' => $product->getId(),
					'model' => str_replace('/', '_', $product->getDocumentModelName()),
					'reference' => $product->getCodeReference(), 
					'quantity' => $stSrv->getStockableDocument($product)->getCurrentStockQuantity()
				);
			}
			$widget['header'] = f_Locale::translate('&modules.catalog.bo.dashboard.Out-of-stock-products;')." : ".$productsCount;
		}
		else
		{
			$widget['header'] = f_Locale::translate('&modules.catalog.bo.dashboard.No-out-of-stock-products;');
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