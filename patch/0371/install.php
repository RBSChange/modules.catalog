<?php
/**
 * catalog_patch_0371
 * @package modules.catalog
 */
class catalog_patch_0371 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$ccps = catalog_ComparablepropertyService::getInstance();
		$prop = $ccps->createQuery()->add(Restrictions::eq('label', 'Disponibilité'))->findUnique();
		if ($prop instanceof catalog_persistentdocument_comparableproperty)
		{
			$prop->setClassName('catalog_AvailabilityComparableProperty');
			$prop->setParameters(null);+
			$prop->save();
		}
	}
}