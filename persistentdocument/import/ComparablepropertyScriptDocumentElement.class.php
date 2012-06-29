<?php
/**
 * catalog_ComparablepropertyScriptDocumentElement
 * @package modules.catalog.persistentdocument.import
 */
class catalog_ComparablepropertyScriptDocumentElement extends import_ScriptDocumentElement
{
	/**
	 * @return catalog_persistentdocument_comparableproperty
	 */
	protected function initPersistentDocument()
	{
		return catalog_ComparablepropertyService::getInstance()->getNewDocumentInstance();
	}
	
	/**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_catalog/comparableproperty');
	}
	
	public function endProcess()
	{
		$children = $this->script->getChildren($this);
		$parameters = array();
		foreach ($children as $child)
		{
			if ($child instanceof catalog_ScriptParameterElement)
			{
				$parameters[] = $child->getValue();
			}
		}
		if (count($parameters))
		{
			$doc = $this->getPersistentDocument();
			$doc->setParameters($parameters);
			$doc->save();
		}
	}
}