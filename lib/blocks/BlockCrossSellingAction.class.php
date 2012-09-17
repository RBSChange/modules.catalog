<?php
/**
 * @package modules.catalog
 * @method catalog_BlockCrossSellingConfiguration getConfiguration()
 */
class catalog_BlockCrossSellingAction extends catalog_BlockCrossSellingListAction
{
	/**
	 * @return boolean
	 */
	protected function getShowIfNoProduct()
	{
		return false;
	}
		
	/**
	 * @return string[]|null the array should contain the following keys: 'url', 'label'
	 */
	protected function getFullListLink()
	{
		$target = $this->getTarget();
		if (!($target instanceof catalog_persistentdocument_product) || !$target->isPublished())
		{
			return null;
		}
		$params = array('catalogParam[cmpref]' => $target->getId(), 'catalogParam[linkType]' => $this->getLinkType());
		$url = LinkHelper::getTagUrl('functional_catalog_crosssellinglist-page', null, $params);
		$label = LocaleService::getInstance()->trans('m.catalog.frontoffice.cross-selling-list', array('ucf'), array(
			'type' => $this->getTypeLabel()));
		return array('url' => $url, 'label' => $label);
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
		return $this->getTypeLabel();
	}
}