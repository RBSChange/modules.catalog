<?php
/**
 * catalog_patch_0376
 * @package modules.catalog
 */
class catalog_patch_0376 extends patch_BasePatch
{
	/**
     * Returns true if the patch modify code that is versionned.
     * If your patch modify code that is versionned AND database structure or content,
     * you must split it into two different patches.
     * @return Boolean true if the patch modify code that is versionned.
     */
	public function isCodePatch()
	{
		return true;
	}
	
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		// Override editors.
		$docNames = array('bundleproduct', 'declinedproduct', 'kit', 'simpleproduct', 'virtualproduct', 'preferences');
		foreach ($docNames as $docName)
		{
			$srcFile = f_util_FileUtils::buildRelativePath(dirname(__FILE__), 'editor', $docName, 'properties.xml');
			$destFile = f_util_FileUtils::buildOverridePath('modules', 'catalog', 'forms', 'editor', $docName, 'properties.xml');
			if (!file_exists($destFile))
			{
				f_util_FileUtils::cp($srcFile, $destFile);
			}
			elseif (file_get_contents($srcFile) !== file_get_contents($destFile))
			{
				$this->logWarning('File (' . $destFile . ') already exist.');
			}
		}
		
		// Override blocks.xml.
		$srcFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'blocks.xml';
		$destFile = f_util_FileUtils::buildOverridePath('modules', 'catalog', 'config', 'blocks.xml');
		if (!file_exists($destFile))
		{
			f_util_FileUtils::cp($srcFile, $destFile);
		}
		elseif (file_get_contents($srcFile) !== file_get_contents($destFile))
		{
			$this->logWarning('File (' . $destFile . ') already exist.');
		}
		
		// Override shopDefaultStructure.xml.
		$srcFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'shopDefaultStructure.xml';
		$destFile = f_util_FileUtils::buildOverridePath('modules', 'catalog', 'setup', 'shopDefaultStructure.xml');
		if (!file_exists($destFile))
		{
			f_util_FileUtils::cp($srcFile, $destFile);
		}
		elseif (file_get_contents($srcFile) !== file_get_contents($destFile))
		{
			$this->logWarning('File (' . $destFile . ') already exist.');
		}
	}
}