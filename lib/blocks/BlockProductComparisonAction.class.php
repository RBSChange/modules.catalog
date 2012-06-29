<?php
/**
 * catalog_BlockProductComparisonAction
 * @package modules.catalog.lib.blocks
 */
class catalog_BlockProductComparisonAction extends website_BlockAction
{
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return string
	 */
	public function execute($request, $response)
	{
		if ($this->isInBackofficeEdition())
		{
			return website_BlockView::NONE;
		}
		
		$cms = catalog_ModuleService::getInstance();
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		if ($shop === null)
		{
			return website_BlockView::NONE;
		}
		$request->setAttribute('shop', $shop);
		
		// Remove product if needed.
		if ($request->hasParameter('removeFromList'))
		{
			foreach ($request->getParameter('selected_product') as $id)
			{
				$cms->removeProductIdFromList(catalog_ModuleService::COMPARISON_LIST, $id);
			}
		}

		// If not logged in, display the warning.
		$ls = LocaleService::getInstance();
		$configKey = 'modules/catalog/productListComparisonPersist';
		if (Framework::getConfigurationValue($configKey) === 'true' && users_UserService::getInstance()->getCurrentFrontEndUser() === null)
		{
			$this->addError($ls->trans('m.catalog.frontoffice.warning-list-not-persisted', array('ucf')));
		}
		$request->setAttribute('blockTitle', $ls->trans('m.catalog.frontoffice.my-favorite-products', array('ucf')));

		// Get products.
		$configuration = $this->getConfiguration();
		if ($configuration->getRestrictProductsToContext())
		{
			$topic = $this->getContext()->getPersistentPage()->getTopic();
			$query = website_SystemtopicService::getInstance()->createQuery()->add(Restrictions::descendentOf($topic->getId()))->add(Restrictions::published());
			$query->createPropertyCriteria('referenceId', 'modules_catalog/shelf');
			$shelfIds = $query->setProjection(Projections::property('referenceId'))->findColumn('referenceId');
			$shelf = catalog_ShelfService::getInstance()->getCurrentShelf();
			if ($shelf !== null)
			{
				array_unshift($shelfIds, $shelf->getId());
			}
			
			$productIds = $cms->getProductIdsFromList(catalog_ModuleService::COMPARISON_LIST);
			
			if (count($shelfIds) > 0)
			{
				$products = catalog_CompiledproductService::getInstance()->createQuery()->add(Restrictions::in('shelfId', $shelfIds))
					->add(Restrictions::in('product.id', $productIds))->add(Restrictions::published())
					->setProjection(Projections::groupProperty('product'))->findColumn('product');
			}
			else
			{
				$products = array();
			}
			
			if (count($products) < count($productIds))
			{
				$this->addError($ls->trans('m.catalog.fo.warning-restricted-list', array('ucf')));
			}
		}
		else
		{
			$products = $cms->getProductsFromList(catalog_ModuleService::COMPARISON_LIST);
		}
		$request->setAttribute('products', $products);
		
		$properties = array();
		foreach ($configuration->getComparableProperties() as $prop)
		{
			$properties[] = $prop->getPropertyInstance();
		}
		$request->setAttribute('properties', $properties);
		
		// Pictograms configuration.
		if ($configuration->getShowProductPictograms())
		{
			$request->setAttribute('showProductPictograms', true);
			$request->setAttribute('showAnimPictogramBlock', ModuleService::getInstance()->moduleExists('marketing'));
		}
		else
		{
			$request->setAttribute('showProductPictograms', false);
		}
		
		return website_BlockView::SUCCESS;
	}
}