<?php
/**
 * catalog_AttributefolderScriptDocumentElement
 * @package modules.catalog.persistentdocument.import
 */
class catalog_AttributefolderScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return catalog_persistentdocument_attributefolder
     */
    protected function initPersistentDocument()
    {
    	$folder = catalog_AttributefolderService::getInstance()->getAttributeFolder();
    	if ($folder === null)
    	{
    		$folder = catalog_AttributefolderService::getInstance()->getNewDocumentInstance();
    	}
    	return $folder;
    }
    
    /**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_catalog/attributefolder');
	}

	public function endProcess()
	{
		$children = $this->script->getChildren($this);
		if (count($children))
		{
			$attributeScripts = array();
			foreach ($children as $child)
			{
				if ($child instanceof catalog_ScriptAttributeDefinitionElement)
				{
					$attributeScripts[] = $child;
				}
			}
				
			if (count($attributeScripts) > 0)
			{
				$folderAttribute = $this->getPersistentDocument();
				$this->updateConfiguredAttributes($folderAttribute, $attributeScripts);
				$folderAttribute->save();
			}
		}
	}
	
	/**
	 * @param catalog_persistentdocument_attributefolder $attributeFolder
	 * @param catalog_ScriptAttributeDefinitionElement[] $attributeScripts
	 */
	protected function updateConfiguredAttributes($attributeFolder, $attributeScripts)
	{
		$attributes = array();
		foreach ($attributeScripts as $attribute)
		{
			$infos = $attribute->getAttributeInfos();
			$attributes[] = $infos;
		}
		$attributeFolder->setAttributesJSON(json_encode($attributes));
	}
}