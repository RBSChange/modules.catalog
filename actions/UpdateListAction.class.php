<?php
/**
 * catalog_UpdateListAction
 * @package modules.catalog.actions
 * 
 * Handled paramaters:
 *  - listName ('favorite' by default): the list name (ex: 'favorite', 'consulted', 'monModule_maListe)
 *  - mode ('add' by default): the update mode ('clear', 'replace', 'add' or 'remove')
 *  - productIds: an array of product ids
 *  - backurl: the url where to redirect after list update
 *  - refererPageId: the page from where the update is called. Used only if 'backurl' is omitted to look for a functionnal page to redirect
 */
class catalog_UpdateListAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		// Get parameters.
		$ls = LocaleService::getInstance();
		$formatters = array('ucf', 'html');
		$productIds = $this->getGetProductIdsFromRequest($request);
		$mode = $request->getParameter('mode', 'add');
		if (is_array($mode))
		{
			$mode = f_util_ArrayUtils::firstElement(array_keys($mode));
		}
		$listName = $request->getParameter('listName', catalog_ProductList::FAVORITE);
		$list = catalog_ModuleService::getInstance()->getProductList($listName);
		list($module, $name) = (strpos($listName, '_') !== false) ? explode('_', $listName) : array('catalog', $listName);
		
		// Update the list.
		if ($mode !== 'clear' && count($productIds) === 0)
		{
			$ok = false;
			website_SessionMessage::addVolatileError($ls->transFO('m.'.$module.'.fo.no-selected-product', $formatters));
		}
		else 
		{	
			$ok = true;		
			switch ($mode)
			{
				case 'clear':
					foreach ($list->getProductIds() as $id)
					{
						$list->removeProductId($id);
					}
					break;
				
				case 'replace':
					foreach ($list->getProductIds() as $id)
					{
						$list->removeProductId($id);
					}
				case 'add':
					foreach ($productIds as $id)
					{
						$list->addProductId($id);
					}
					break;
					
				case 'remove':
					foreach ($productIds as $id)
					{
						$list->removeProductId($id);
					}
					break;
					
				default:
					$ok = false;
					website_SessionMessage::addVolatileError($ls->transFO('m.'.$module.'.fo.unknown-update-mode', $formatters));
					break;
			}
		}
		
		// Add message.
		if ($ok)
		{
			$list->saveList();
			$listUrl = $this->getListUrl($request, $module, $name);
			Framework::fatal(__METHOD__ . ' ' . $listUrl);
			if ($listUrl)
			{
				$replacements = array('listUrl' => $listUrl);
				website_SessionMessage::addVolatileMessage($ls->transFO('m.'.$module.'.fo.productlist-'.$name.'-updated-link', $formatters, $replacements));
			}
			else
			{
				website_SessionMessage::addVolatileMessage($ls->transFO('m.'.$module.'.fo.productlist-'.$name.'-updated', $formatters));
			}
		}
		
		// Redirect.
		$backUrl = $request->getParameter('backurl');
		if (!$backUrl)
		{
			$backUrl = $this->getListUrl($request, $module, $name);
		}
		if (!$backUrl)
		{
			$website = website_WebsiteModuleService::getInstance()->getCurrentWebsite();
			$backUrl = LinkHelper::getDocumentUrl($website);
		}
		HttpController::getInstance()->redirectToUrl($backUrl);
	}
	
	/**
	 * Returns an array of the documents IDs received by this action.
	 *
	 * All the IDs contained in the resulting array are REAL integer values, not
	 * strings.
	 *
	 * @param Request $request
	 * @return array<integer>
	 */
	protected function getGetProductIdsFromRequest($request)
	{
		$docIds = $request->getParameter('productIds');
		// For compatibility with product list block.
		if ($docIds === null && $request->hasModuleParameter('catalog', 'selected_product'))
		{
			$docIds = $request->getModuleParameter('catalog', 'selected_product');
		}
		
		if (is_string($docIds) && intval($docIds) == $docIds)
		{
			$docIds = array(intval($docIds));
		}		
		elseif (is_int($docIds)) 
		{
			$docIds = array($docIds);
		}
		elseif (is_array($docIds))
		{
			foreach ($docIds as $index => $docId)
			{
				if (strval(intval($docId)) === $docId)
				{
					$docIds[$index] = intval($docId);
				}
				else if (!is_int($docId))
				{
					unset($docIds[$index]);
				}
			}
		}
		else
		{
			$docIds = array();
		}
		return $docIds;
	}
	
	/**
	 * @var string
	 */
	private $listUrl = false;
	
	/**
	 * @param $module string
	 * @param $list string
	 * @return string
	 */
	protected function getListUrl($request, $module, $name)
	{
		if ($this->listUrl === false)
		{
			$doc = null;
			$ts = TagService::getInstance();
			$refererPageId = $request->getParameter('refererPageId');
			if ($refererPageId)
			{
				$ts = TagService::getInstance();
				$tag = 'functional_'.$module.'_productlist-'.$name;
				$page = DocumentHelper::getDocumentInstance($refererPageId);
				if ($ts->hasTag($page, $tag))
				{
					$doc = $page;
				}
				else
				{
					$doc = $ts->getDocumentBySiblingTag($tag, $page, false);
				}
			}
				
			$website = website_WebsiteModuleService::getInstance()->getCurrentWebsite();
			if ($doc === null)
			{
				$tag = 'contextual_website_website_modules_'.$module.'_'.$name.'-product-list';
				$doc = $ts->getDocumentByContextualTag($tag, $website, false);
			}
				
			if ($doc !== null)
			{
				$this->listUrl = LinkHelper::getDocumentUrlForWebsite($doc, $website);
			}
			else
			{
				$this->listUrl = null;
			}
		}
		return $this->listUrl;
	}
	
	/**
	 * @return boolean
	 */
	public function isSecure()
	{
		return false;
	}
}