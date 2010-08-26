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