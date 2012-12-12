<?php
/**
 * @package modules.catalog
 * @method catalog_BlockCrossSellingListConfiguration getConfiguration()
 */
class catalog_BlockCrossSellingListAction extends catalog_BlockProductlistBaseAction
{
	/**
	 * @param f_mvc_Response $response
	 * @return integer[]
	 */
	protected function getProductIdArray($request)
	{
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		$linkType = $this->getLinkType();
		if ($shop === null || !$linkType)
		{
			return array();
		}
		$target = $this->getTarget();
		if (!($target instanceof catalog_persistentdocument_product) || !$target->isPublished())
		{
			return array();
		}
		$request->setAttribute('targetProduct', $target);
		return catalog_CompiledcrossitemService::getInstance()->getDisplayableLinkedIds($target, $linkType, $shop);
	}
		
	/**
	 * @return string
	 */
	protected function getBlockTitle()
	{
		$title = $this->getConfigurationValue('blockTitle', null);
		if ($title)
		{
			return f_util_HtmlUtils::textToHtml($title);
		}
		$target = $this->getTarget();
		if (!($target instanceof catalog_persistentdocument_product))
		{
			return null;
		}
		$productLink = '<a class="link" href="' . LinkHelper::getDocumentUrl($target) . '">' . $target->getLabelAsHtml() . '</a>';
		$list = list_ListService::getInstance()->getByListId('modules_catalog/crosssellinglinktypes');
		$linkType = $list->getItemByValue($this->getLinkType());
		if ($linkType)
		{
			return $linkType->getLabel() . ' - ' . $productLink;
		}
		else
		{
			return LocaleService::getInstance()->transFO('m.catalog.fo.linked-products', array('ucf')) . ' - ' . $productLink;
		}
	}
	
	/**
	 * @return string
	 */
	protected function getTypeLabel()
	{
		$linkType = $this->getLinkType();
		$list = list_ListService::getInstance()->getByListId('modules_catalog/crosssellinglinktypes');
		return f_util_HtmlUtils::textToHtml($list->getItemByValue($linkType)->getLabel());
	}
	
	/**
	 * @return catalog_persistentdocument_product
	 */
	protected function getTarget()
	{
		$targetId = $this->findParameterValue('cmpref', null, 'CRPG');
		return DocumentHelper::getDocumentInstanceIfExists($targetId);
	}
	
	/**
	 * @return string
	 */
	protected function getLinkType()
	{
		return $this->findParameterValue('linkType', null, 'CRPG');
	}
}