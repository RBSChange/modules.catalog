<?php
/**
 * catalog_patch_0300
 * @package modules.catalog
 */
class catalog_patch_0300 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		parent::execute();
		
		// Add new field in preferences.
		$this->executeSQLQuery("ALTER TABLE `m_catalog_doc_preferences` ADD `enablecomments` tinyint(1) NOT NULL default '0'");
		
		// Init field in prefrences.
		$prefs = ModuleService::getPreferencesDocument('catalog');
		if ($prefs !== null)
		{
			$prefs->setEnablecomments(true);
			$prefs->save();
		}
	}

	/**
	 * Returns the name of the module the patch belongs to.
	 *
	 * @return String
	 */
	protected final function getModuleName()
	{
		return 'catalog';
	}

	/**
	 * Returns the number of the current patch.
	 * @return String
	 */
	protected final function getNumber()
	{
		return '0300';
	}
}