<?php
/**
 * catalog_patch_0309
 * @package modules.catalog
 */
class catalog_patch_0309 extends patch_BasePatch
{
//  by default, isCodePatch() returns false.
//  decomment the following if your patch modify code instead of the database structure or content.
    /**
     * Returns true if the patch modify code that is versionned.
     * If your patch modify code that is versionned AND database structure or content,
     * you must split it into two different patches.
     * @return Boolean true if the patch modify code that is versionned.
     */
//	public function isCodePatch()
//	{
//		return true;
//	}
 
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$sqlPath = f_util_FileUtils::buildChangeBuildPath('modules', 'catalog', 'dataobject', 'm_catalog_doc_kititem.mysql.sql');
		if (!file_exists($sqlPath))
		{
			$this->execChangeCommand('compile-documents');
		}
		$this->executeSQLScript($sqlPath);
		
		$sqlPath = f_util_FileUtils::buildChangeBuildPath('modules', 'catalog', 'dataobject', 'm_catalog_doc_product_catalog_kit.mysql.sql');
		$this->executeSQLScript($sqlPath);
		
		$sqlPath = f_util_FileUtils::buildChangeBuildPath('modules', 'catalog', 'dataobject', 'm_catalog_doc_product_i18n_catalog_kit.mysql.sql');
		$this->executeSQLScript($sqlPath);
		
		$sqlPath = f_util_FileUtils::buildChangeBuildPath('modules', 'catalog', 'dataobject', 'm_catalog_doc_price_catalog_lockedprice.mysql.sql');
		$this->executeSQLScript($sqlPath);		

		$this->log('compile-db-schema ...');
		$this->execChangeCommand('compile-db-schema');
		$this->log('compile-locales catalog ...');
		$this->execChangeCommand('compile-locales', array('catalog'));
		$this->log('compile-editors-config ...');
		$this->execChangeCommand('compile-editors-config');

	}

	private function executeSQLScript($sqlPath)
	{
		$sql = file_get_contents($sqlPath);
		foreach(explode(";", $sql) as $query)
		{
			$query = trim($query);
			if (empty($query))
			{
				continue;
			}
			try
			{
				$this->executeSQLQuery($query);
			}
			catch (Exception $e)
			{
				Framework::warn(__METHOD__ . ' ' . $e->getMessage());
			}
		}
	}
	
	/**
	 * @return String
	 */
	protected final function getModuleName()
	{
		return 'catalog';
	}

	/**
	 * @return String
	 */
	protected final function getNumber()
	{
		return '0309';
	}
}