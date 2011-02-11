<?php
/**
 * catalog_patch_0355
 * @package modules.catalog
 */
class catalog_patch_0355 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$this->log('compile-documents ...');
		$this->execChangeCommand('compile-documents');
		
		$this->log('Update prices ...');
		$this->updatePriceDocument();
		
		$this->log('Update Shop ...');
		$this->updateShopDocument();
		
		$this->log('Update Tax ...');
		$this->updateTaxDocument();
		
		$this->log('Update ShippingFilter ...');
		$this->updateShippingFilterDocument();
		
		$pp = f_persistentdocument_PersistentProvider::getInstance();
		$query = "UPDATE `m_catalog_doc_price` SET `taxcategory` = `taxcode` WHERE `taxcode` IS NOT NULL";
		$pp->executeSQLScript($query);
		
		$pp = f_persistentdocument_PersistentProvider::getInstance();
		$query = "UPDATE `m_catalog_doc_shippingfilter` SET `taxcategory` = `taxcode` WHERE `taxcode` IS NOT NULL";
		$pp->executeSQLScript($query);		

		$query = "SELECT relation_order, s.`document_id` AS shopid, f_relation.relation_id2 as countryid, z.code, pricemode FROM `m_catalog_doc_shop` AS s INNER JOIN f_relation on s.`document_id` = f_relation.relation_id1 AND relation_name = 'shippingZone' INNER JOIN m_zone_doc_zone AS z on z.`document_id` = f_relation.relation_id2 ORDER BY relation_id1, relation_order";
		$stmt = $pp->getDriver()->prepare($query);
		$stmt->execute();
		$shopZones = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$query = "SELECT distinct `shopid`, `taxcode` FROM `m_catalog_doc_price`";
		$stmt = $pp->getDriver()->prepare($query);
		$stmt->execute();
		$taxeCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$query = "SELECT `document_id`, `rate`, `code`, `document_label` FROM `m_catalog_doc_tax` WHERE `shopid` IS NULL";
		$stmt = $pp->getDriver()->prepare($query);
		$stmt->execute();
		$taxes= $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		
		foreach ($shopZones as $row) 
		{
			if ($row['relation_order'] == '0')
			{
				$taxeZone = substr($row['code'], 0, 20);
				$query = "UPDATE `m_catalog_doc_shop` SET `defaulttaxzone` = '".$taxeZone."', `botaxzone` = '".$taxeZone."' WHERE `document_id` = " . $row['shopid'];
				$pp->executeSQLScript($query);
			}
		}
		
		foreach ($shopZones as $row) 
		{
			$taxeZone = substr($row['code'], 0, 6);
			$shopId = $row['shopid'];
			foreach ($taxeCategories as $taxeRow) 
			{
				if ($taxeRow['shopid'] == $shopId)
				{
					foreach ($taxes as $taxRateRow)
					{
						if ($taxRateRow['code'] == $taxeRow['taxcode'])
						{
							$taxDoc = catalog_TaxService::getInstance()->getNewDocumentInstance();
							$taxDoc->setLabel($taxRateRow['document_label']);
							$taxDoc->setRate($taxRateRow['rate']);
							$taxDoc->setShopId($shopId);
							$taxDoc->setTaxCategory($taxRateRow['code']);
							$taxDoc->setTaxZone($taxeZone);
							$taxDoc->save();
							
							$this->log("Add tax: " . $taxDoc->getLabel() . ' Shop: ' . $taxDoc->getShopId() . ' Category: ' . 
								$taxDoc->getTaxCategory() . ' Zone: ' . $taxDoc->getTaxZone() . ' Rate: ' . $taxDoc->getRate());
						}			
					} 
				}
			}		
			catalog_TaxService::getInstance()->addNoTaxForShopAndZone($shopId, $taxeZone);
		}
		
		
		
		//Delete old Taxe document
		foreach ($taxes as $row) 
		{
			catalog_persistentdocument_tax::getInstanceById($row['document_id'])->delete();
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
		return '0355';
	}
	
	private function updatePriceDocument()
	{
		$newPath = f_util_FileUtils::buildWebeditPath('modules/catalog/persistentdocument/price.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'catalog', 'price');
		$newProp = $newModel->getPropertyByName('taxCategory');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'price', $newProp);
		
		$this->executeModuleScript('list-taxcategory.xml', 'catalog');
	}
	
	private function updateShopDocument()
	{
		$pp = f_persistentdocument_PersistentProvider::getInstance();
		
		$newPath = f_util_FileUtils::buildWebeditPath('modules/catalog/persistentdocument/shop.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'catalog', 'shop');
		$newProp = $newModel->getPropertyByName('defaultTaxZone');
		$pp->addProperty('catalog', 'shop', $newProp);

		$newProp = $newModel->getPropertyByName('boTaxZone');
		$pp->addProperty('catalog', 'shop', $newProp);	

	}
	
	private function updateTaxDocument()
	{
		$newPath = f_util_FileUtils::buildWebeditPath('modules/catalog/persistentdocument/tax.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'catalog', 'tax');
		$newProp = $newModel->getPropertyByName('taxCategory');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'tax', $newProp);
		
		$newProp = $newModel->getPropertyByName('taxZone');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'tax', $newProp);

		$newProp = $newModel->getPropertyByName('shopId');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'tax', $newProp);
	}	
	
	private function updateShippingFilterDocument()
	{
		$newPath = f_util_FileUtils::buildWebeditPath('modules/catalog/persistentdocument/shippingfilter.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'catalog', 'shippingfilter');
		$newProp = $newModel->getPropertyByName('taxCategory');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'shippingfilter', $newProp);
	}
}