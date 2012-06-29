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
	 * @return string
	 */
	public function execute($request, $response)
	{
		$subShelves = $this->getSubShelves();
		if (count($subShelves) < 1 || $this->isInBackofficeEdition())
		{
			return website_BlockView::NONE;
		}
		
		
		
		$request->setAttribute('currentShelf', $this->getCurrentEcommerceContainer());
		$request->setAttribute('shelves', $subShelves);
		$request->setAttribute('redirectFormAction', LinkHelper::getActionUrl('catalog', 'Redirect', array('parentTopic' => $this->getParentId())));
		return website_BlockView::SUCCESS;
	}
	
	/**
	 * @return array<String, String>
	 */
	public function getMetas()
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
		return array();
	}
	
	private $container = null;
	
	private function getCurrentEcommerceContainer()
	{
		if ($this->container === null)
		{
			$topicId = $this->getParentId();
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
	 * @return integer
	 */
	protected function getParentId()
	{
		return end($this->getContext()->getAncestorIds());
	}
	
	/**
	 * @return catalog_persistentdocument_shelf[]
	 */
	private function getSubShelves()
	{
		$topicId = $this->getParentId();
		$query = catalog_ShelfService::getInstance()->createQuery()->add(Restrictions::published());
		$visibility = Restrictions::in('navigationVisibility', array(website_ModuleService::VISIBLE, website_ModuleService::HIDDEN_IN_SITEMAP_ONLY));
		$query->createCriteria('topic')->add(Restrictions::childOf($topicId))->add(Restrictions::published())->add($visibility);
		return $query->find();
	}
}