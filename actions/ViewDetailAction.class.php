<?php
/**
 * catalog_ViewDetailAction
 * @package modules.catalog
 */
class catalog_ViewDetailAction extends f_action_BaseAction
{
	/**
	 * @return Boolean true
	 */
	public function isSecure()
	{
		return false;
	}

	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$module = AG_ERROR_404_MODULE;
		$action = AG_ERROR_404_ACTION;

		try
		{
			$document = DocumentHelper::getDocumentInstance($request->getModuleParameter('catalog', 'cmpref'));
		}
		catch (Exception $e)
		{
			$document = null;
			Framework::exception($e);
		}

		if ($document !== null)
		{
			// Find detail page for the document to display.
			if (!is_null($page = $this->getDetailPage($document)))
			{
				$request->setParameter(K::PAGE_REF_ACCESSOR, $page->getId());
				$module = 'website';
				$action = 'Display';
			}
		}

		$context->getController()->forward($module, $action);
		return View::NONE;
	}

	/**
	 * @param f_persistentdocument_PersistentDocumentImpl $document
	 * @return website_persistentdocument_page
	 */
	private function getDetailPage($document)
	{
		if ($document instanceof catalog_persistentdocument_shelf)
		{
			return $this->getDetailPageForShelf($document);
		}
		else if ($document instanceof catalog_persistentdocument_shop)
		{
			return $this->getDetailPageForShop($document);
		}
		else if ($document instanceof catalog_persistentdocument_product)
		{
			return $this->getDetailPageForProduct($document);
		}
		else if ($document instanceof f_persistentdocument_PersistentDocument)
		{
			return $this->getDetailPageForOtherDocument($document);
		}
		return null;
	}

	/**
	 * @param catalog_persistentdocument_shelf $shelf
	 * @return website_persistentdocument_page
	 */
	private function getDetailPageForShelf($shelf)
	{
		Framework::debug("Detail page for shelf (".get_class($shelf).")");
		$website = website_WebsiteModuleService::getInstance()->getCurrentWebsite();
		$query = website_SystemtopicService::getInstance()->createQuery()
			->add(Restrictions::descendentOf($website->getId()))
			->add(Restrictions::eq('shelf.id', $shelf->getId()));
		$topic = $query->findUnique();
		if ($topic !== null)
		{
			return $topic->getIndexPage();
		}
		return null;
	}

	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return website_persistentdocument_page
	 */
	private function getDetailPageForShop($shop)
	{
		return $shop->getTopic()->getIndexPage();
	}

	/**
	 * @param catalog_persistentdocument_product $product
	 * @return website_persistentdocument_page
	 */
	private function getDetailPageForProduct($product)
	{
		$currentShop = catalog_ShopService::getInstance()->getCurrentShop();
		$primaryShelf = $product->getPrimaryShelf(website_WebsiteModuleService::getInstance()->getCurrentWebsite());
		if ($primaryShelf !== null)
		{
			$topic = $primaryShelf->getDocumentService()->getRelatedTopicByShop($primaryShelf, $currentShop);
			if ($topic !== null)
			{
				return website_PageService::getInstance()->createQuery()
					->add(Restrictions::childOf($topic->getId()))
					->add(Restrictions::hasTag('functional_catalog_product-detail'))
					->findUnique();
			}
		}
		return null;
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return website_persistentdocument_page
	 */
	private function getDetailPageForOtherDocument($document)
	{
		Framework::debug("Detail page for other document (".get_class($document).")");
		
		try
		{
			$page = TagService::getInstance()->getDocumentBySiblingTag(
				$this->getFunctionalTag($document),
				$document
			);
		}
		catch (TagException $e)
		{
			$page = null;
			Framework::exception($e);
		}

		return $page;
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return String
	 */
	private function getFunctionalTag($document)
	{
		$model = $document->getPersistentModel();
		if (!is_null($sourceModel = $model->getSourceInjectionModel()))
		{
			$model = $sourceModel;
		}
		return 'functional_catalog_' . $model->getDocumentName() .'-detail';
	}
}