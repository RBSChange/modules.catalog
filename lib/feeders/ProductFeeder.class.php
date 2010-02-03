<?php
/**
 * @package modules.catalog.lib.feeders
 */
abstract class catalog_ProductFeeder
{
	/**
	 * @return catalog_ProductFeeder
	 */
	abstract static function getInstance();
	
	/**
	 * @param Array<Strin, mixed> $parameters
	 * @return catalog_persistentdocument_product[]
	 */
	abstract public function getProductArray($parameters);
	
	/**
	 * @return String
	 */
	public function getLabelKey()
	{
		list($module, $class) = explode('_', get_class($this));
		return 'modules.'.$module.'.bo.general.Feeder-'.$class;
	}
	
	/**
	 * Returns the localized human-readable feeder label.
	 * @return String
	 */
	public function getLabel()
	{
		return f_Locale::translateUI('&'.$this->getLabelKey().';');
	}
}