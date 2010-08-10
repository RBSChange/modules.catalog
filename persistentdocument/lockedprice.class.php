<?php
/**
 * Class where to put your custom methods for document catalog_persistentdocument_lockedprice
 * @package modules.catalog.persistentdocument
 */
class catalog_persistentdocument_lockedprice extends catalog_persistentdocument_lockedpricebase 
{
	/**
	 * @var boolean
	 */
	private $ignoreConflicts = false;
	
	/**
	 * @return boolean
	 */
	public function getIgnoreConflicts()
	{
		return $this->ignoreConflicts;
	}
	
	/**
	 * @param boolean $ignoreConflicts
	 */
	public function setIgnoreConflicts($ignoreConflicts)
	{
		$this->ignoreConflicts = $ignoreConflicts;
	}
	
	/**
	 * @param string $moduleName
	 * @param string $treeType
	 * @param array<string, string> $nodeAttributes
	 */
//	protected function addTreeAttributes($moduleName, $treeType, &$nodeAttributes)
//	{
//	}
	
	/**
	 * @param string $actionType
	 * @param array $formProperties
	 */
//	public function addFormProperties($propertiesNames, &$formProperties)
//	{	
//	}
}