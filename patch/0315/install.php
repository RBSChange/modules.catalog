<?php
/**
 * catalog_patch_0315
 * @package modules.catalog
 */
class catalog_patch_0315 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$this->executeLocalXmlScript('list.xml');
		
		$newPath = f_util_FileUtils::buildWebeditPath('modules/catalog/persistentdocument/compiledproduct.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'catalog', 'compiledproduct');
		$newProp = $newModel->getPropertyByName('firstpublicationdate');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'compiledproduct', $newProp);
		$newProp = $newModel->getPropertyByName('lastpublicationdate');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'compiledproduct', $newProp);
		
		foreach (catalog_CompiledproductService::getInstance()->createQuery()->add(Restrictions::published())->find() as $product)
		{
			$product->setFirstpublicationdate($product->getCreationdate());
			$product->setLastpublicationdate($product->getModificationdate());
			$product->save();
		}
		
		// Suppression de thresholdmax
		$archivePath = f_util_FileUtils::buildWebeditPath('modules/catalog/patch/0315/price-1.xml');
		$oldModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($archivePath), 'catalog', 'price');
		$oldProp = $oldModel->getPropertyByName('thresholdMax');
		f_persistentdocument_PersistentProvider::getInstance()->delProperty('catalog', 'price', $oldProp);
		
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
		return '0315';
	}
}