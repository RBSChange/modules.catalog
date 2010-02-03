<?php
/**
 * catalog_BlockShelfContextualListAction
 * @package modules.catalog
 */
class catalog_BlockShelfContextualListAction extends website_BlockAction
{
	/**
	 * @see website_BlockAction::execute()
	 *
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	function execute($request, $response)
	{
		$subShelves = $this->getSubShelves();
		$request->setAttribute('currentShelf', $this->getCurrentEcommerceContainer());
		$request->setAttribute('shelves', $this->getSubShelves());
		$request->setAttribute('redirectFormAction', LinkHelper::getUrl('catalog', 'Redirect'));
		if ($this->isInBackoffice() && count($subShelves) < 1)
		{
			return block_BlockView::NONE;
		}
		else
		{
			return block_BlockView::SUCCESS;
		}
	}
	
	/**
	 * @return array<String, String>
	 */
	function getMetas()
	{
		$container = $this->getCurrentEcommerceContainer();
		if ($container instanceof catalog_persistentdocument_shelf)
		{
			$referencingService = catalog_ReferencingService::getInstance();
			return array("title" => $referencingService->getPageTitleByShelf($container), "description" => $referencingService->getPageDescriptionByShelf($container), "keywords" => $referencingService->getPageKeywordsByShelf($container));
		}
		else if ($container instanceof catalog_persistentdocument_shop)
		{
			$referencingService = catalog_ReferencingService::getInstance();
			return array("title" => $referencingService->getPageTitleByShop($container), "description" => $referencingService->getPageDescriptionByShop($container), "keywords" => $referencingService->getPageKeywordsByShop($container));
		}
	}
	
	private $container = null;
	
	private function getCurrentEcommerceContainer()
	{
		if ($this->container === null)
		{
			$topicId = $this->getContext()->getNearestContainerId();
			$topic = DocumentHelper::getDocumentInstance($topicId);
			if ($topic instanceof website_persistentdocument_systemtopic)
			{
				$shelf = DocumentHelper::getDocumentInstance($topic->getReferenceId());
				if ($shelf instanceof catalog_persistentdocument_shelf)
				{
					$this->container = $shelf;
				}
				else
				{
					$this->container = catalog_ShopService::getInstance()->getCurrentShop();
				}
			}
		}
		return $this->container;
	}
	
	/**
	 * @return catalog_persistentdocument_shelf[]
	 */
	private function getSubShelves()
	{
		$topicId = $this->getContext()->getNearestContainerId();
		$query = catalog_ShelfService::getInstance()->createQuery()->add(Restrictions::published());
		$query->createCriteria('topic')->add(Restrictions::childOf($topicId))->add(Restrictions::published());
		return $query->find();
	}
}