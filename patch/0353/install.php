<?php
/**
 * catalog_patch_0353
 * @package modules.catalog
 */
class catalog_patch_0353 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		// Implement your patch here.
		$this->executeModuleScript('taxes.xml', 'catalog');
		$this->executeModuleScript('currencies.xml', 'catalog');
		
		// Rajouter le currencyId.
		$newPath = f_util_FileUtils::buildWebeditPath('modules/catalog/persistentdocument/price.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'catalog', 'price');
		$newProp = $newModel->getPropertyByName('currencyId');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'price', $newProp);
		$this->executeSQLQuery('UPDATE m_catalog_doc_price set currencyId = (SELECT c.document_id FROM m_catalog_doc_currency as c INNER JOIN m_catalog_doc_shop as s ON s.currencyCode = c.code WHERE s.document_id = m_catalog_doc_price.shopId)');
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
		return '0353';
	}
}