<?php
/**
 * Class where to put your custom methods for document catalog_persistentdocument_billingareafolder
 * @package modules.catalog.persistentdocument
 */
class catalog_persistentdocument_billingareafolder extends catalog_persistentdocument_billingareafolderbase 
{	
	/**
	 * @return string
	 */
	public function getTreeNodeLabel()
	{
		$label = parent::getTreeNodeLabel();
		if ($label === 'billingareafolder')
		{
			return LocaleService::getInstance()->transBO($this->getPersistentModel()->getLabelKey(), array('ucf'));
		}
		return $label;
	}
}