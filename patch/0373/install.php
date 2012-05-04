<?php
/**
 * catalog_patch_0373
 * @package modules.catalog
 */
class catalog_patch_0373 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		foreach (catalog_AttributefolderService::getInstance()->createQuery()->find() as $folder)
		{
			/* @var $folder generic_persistentdocument_folder */
			$folder->setLabel('m.catalog.document.attributefolder.document-name');
			$folder->save();
		}
		
		foreach (catalog_ComparablepropertyfolderService::getInstance()->createQuery()->find() as $folder)
		{
			/* @var $folder generic_persistentdocument_folder */
			$folder->setLabel('m.catalog.document.comparablepropertyfolder.document-name');
			$folder->save();
		}
		
		$newPath = f_util_FileUtils::buildWebeditPath('modules/catalog/persistentdocument/product.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'catalog', 'product');
		$newProp = $newModel->getPropertyByName('minOrderQuantity');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'product', $newProp);
		
		$this->executeSQLQuery('UPDATE m_catalog_doc_product SET minorderquantity = 1 WHERE minorderquantity IS NULL;');
		

		$attFolder = catalog_AttributefolderService::getInstance()->getAttributeFolder();
		if ($attFolder)
		{
			$val = $attFolder->getSerializedattributes();
			if (f_util_StringUtils::isNotEmpty($val))
			{
				$data = unserialize($val);
				$newdata = array();
				foreach ($data as $array)
				{
					$attr = new catalog_AttributeDefinition();
					$attr->initFromArray($array);
					
					if (isset($array['label']))
					{
						$this->log('Add local: ' . $attr->getI18nLabelKey() . ' -> ' . $array['label']);
						catalog_AttributefolderService::getInstance()->setDefaultI18nLabel($attr->getI18nLabelKey(), $array['label']);
					}
					$newdata[$attr->getCode()] = $attr->toArray();
				}
				$attFolder->setSerializedattributes(serialize(array_values($newdata)));
				if ($attFolder->isModified())
				{
					$this->log('Update AttributeFolder: ' . $attFolder->getId());
					$attFolder->save();
				}
			}
		}
	}
}