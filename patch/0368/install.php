<?php
/**
 * catalog_patch_0368
 * @package modules.catalog
 */
class catalog_patch_0368 extends patch_BasePatch
{
 
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$list = list_ListService::getInstance()->getByListId('modules_catalog/currency');
		if ($list === null)
		{
			$list = list_DynamiclistService::getInstance()->getNewDocumentInstance();
			$list->setLabel('Devises');
			$list->setListid('modules_catalog/currency');	
			$list->save(ModuleService::getInstance()->getSystemFolderId('list', 'catalog'));
		}
		
		$list = list_ListService::getInstance()->getByListId('modules_catalog/defaulttaxzones');
		if ($list instanceof list_persistentdocument_valuededitablelist)
		{
			$defaultItems = array("FR-CONTINENTAL" => "France continentale", "20" => "Corse", 
					"DE" => "Allemagne", "GB" => "Royaume-Uni", "CH" => "Suisse");
			foreach ($list->getItemdocumentsArray() as $di)
			{
				/* @var $di list_persistentdocument_valueditem */
				unset($defaultItems[$di->getValue()]);
			}
			
			if (count($defaultItems))
			{
				foreach ($defaultItems as $v => $l)
				{
					$i = list_ValueditemService::getInstance()->getNewDocumentInstance();
					$i->setLabel($l);
					$i->setValue($v);
					$list->addItemdocuments($i);
				}
				$list->save();
			}
		}
		
		$this->checkBillingAreaDocument();
		
		$billingareafolder = catalog_BillingareafolderService::getInstance()->getDefault();
		$this->log('Add Billing Area Folder: ' .  $billingareafolder->getId());
		
				
// 			DELETED
// 		    <add name="currencyCode" type="String" min-occurs="1" from-list="modules_catalog/shopcurrency" default-value="EUR"/>
// 		    <add name="currencyPosition" type="String" min-occurs="1" localized="true" from-list="modules_catalog/currencyposition" default-value="right"/>
// 		    <add name="defaultTaxZone" type="String" min-occurs="1" db-size="20" from-list="modules_catalog/defaulttaxzones"/>
// 		    <add name="boTaxZone" type="String" db-size="20" from-list="modules_catalog/defaulttaxzones"/>
// 		    <add name="billingZone" type="modules_zone/zone" min-occurs="1"/>

		$this->log('Update Shop DB properties...');
		$newPath = f_util_FileUtils::buildWebeditPath('modules/catalog/persistentdocument/shop.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'catalog', 'shop');
		
		$newProp = $newModel->getPropertyByName('billingAreas');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'shop', $newProp);
		$this->execChangeCommand('compile-db-schema');
		


		//			DELETE
		//     		<add name="shopId" type="Integer" min-occurs="1" />
		$this->log('Update Tax DB properties...');
		$newPath = f_util_FileUtils::buildWebeditPath('modules/catalog/persistentdocument/tax.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'catalog', 'tax');
		
		$newProp = $newModel->getPropertyByName('billingAreaId');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'tax', $newProp);
				
// 			DELETED 
//     		<add name="valueWithoutTax" type="Double" min-occurs="1"><constraints>min:0</constraints></add>
//     		<add name="oldValueWithoutTax" type="Double"><constraints>min:0</constraints></add>
//     		<add name="currencyId" type="Integer" min-occurs="1"/>
		$this->log('Update Price DB properties...');
		$newPath = f_util_FileUtils::buildWebeditPath('modules/catalog/persistentdocument/price.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'catalog', 'price');
		
		$newProp = $newModel->getPropertyByName('billingAreaId');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'price', $newProp);
		
		$newProp = $newModel->getPropertyByName('value');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'price', $newProp);
		
		$newProp = $newModel->getPropertyByName('valueWithoutDiscount');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'price', $newProp);
		
		$newProp = $newModel->getPropertyByName('storeWithTax');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'price', $newProp);
				
		foreach (catalog_ShopService::getInstance()->createQuery()->find() as $shop)
		{
		
			$this->log('Migrate Shop: ' . $shop->getTreeNodeLabel() . ' (' . $shop->getId() . ')');
			$this->migrateBillingArea($shop, $billingareafolder);
			$this->migrateTax($shop);
			$this->migratePrice($shop);
		}
	}
	
	private function checkBillingAreaDocument()
	{
		$path = f_util_FileUtils::buildChangeBuildPath('modules', 'catalog', 'dataobject', 'm_catalog_doc_billingarea.mysql.sql');
		if (!file_exists($path))
		{
			$this->log('Compile documents');
			$this->execChangeCommand('compile-documents');
		}
		
		$this->log('Generate Catalog Database');
		$this->execChangeCommand('generate-database', array('catalog'));
	}
	
	/**
	 * 
	 * @param catalog_persistentdocument_shop $shop
	 * @param catalog_persistentdocument_billingareafolder $billingareafolder
	 */
	private function migrateBillingArea($shop, $billingareafolder)
	{
		if ($shop->getBillingAreasCount())
		{
			return;
		}
		
		$query = "SELECT `document_lang`, `currencycode`, `currencyposition`, `defaulttaxzone`, `botaxzone`, `billingzone` FROM `m_catalog_doc_shop` WHERE `document_id` = " . $shop->getId();
		$stmt = $this->executeSQLSelect($query);
		$oldDatas = $stmt->fetchAll();
		if (!is_array($oldDatas) || count($oldDatas) != 1)
		{
			throw new Exception('Old shop data not found');
		}
		$oldData = $oldDatas[0];
		RequestContext::getInstance()->setLang($oldData['document_lang']);
		
		$currency = catalog_CurrencyService::getInstance()->getByCode($oldData['currencycode']);
		if ($currency === null)
		{
			throw new Exception('Currency not found');
		}
		
		$billingAddressZone = DocumentHelper::getDocumentInstance($oldData['billingzone']);
		
		$defaultzone = $oldData['defaulttaxzone'];	
		$zones = zone_ZoneService::getInstance()->getZonesByCode($defaultzone);
		$zone =  (is_array($zones) && count($zones) != 0) ?  $zones[0] : null;
		
		$billingarea = catalog_BillingareaService::getInstance()->getNewDocumentInstance();
		$billingarea->setCurrency($currency);
		$billingarea->setDefaultZone($defaultzone);
		$billingarea->setZone($zone);
		$billingarea->setLabel($zone->getLabel());
		$billingarea->setCurrencyPosition($oldData['currencyposition'] === 'left' ? 'left' : 'right');
		$billingarea->setBoEditWithTax(f_util_StringUtils::isNotEmpty($oldData['botaxzone']));
		$billingarea->setBillingAddressZone($billingAddressZone);
		$billingarea->setStorePriceWithTax(false);

		$billingarea->save($billingareafolder->getId());
		$this->log('Add Billing Area:' . $billingarea->getId());
		
		$shop->addBillingAreas($billingarea);
		$shop->save();
		
		$query = "SELECT `lang_i18n`, `currencyposition_i18n` FROM `m_catalog_doc_shop_i18n` WHERE `document_id` =" . $shop->getId();
		foreach ($this->executeSQLSelect($query)->fetchAll() as $row)
		{
			if ($row['lang_i18n'] == $oldData['document_lang']) {continue;}
			RequestContext::getInstance()->setLang($row['lang_i18n']);
			$billingarea->setLabel($zone->getLabel());
			$billingarea->setCurrencyPosition($oldData['currencyposition_i18n'] === 'left' ? 'left' : 'right');
			$billingarea->save();
		}		
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 */
	private function migrateTax($shop)
	{
		$billingarea = $shop->getBillingAreas(0);
		if ($billingarea === null)
		{
			throw new Exception('Default Billingarea not found');
		}
		
		$query = "SELECT `document_id` FROM `m_catalog_doc_tax` WHERE  `shopid` = " . $shop->getId();
		foreach ($this->executeSQLSelect($query)->fetchAll() as $row)
		{
			$tax = catalog_persistentdocument_tax::getInstanceById($row['document_id']);
			$this->log('Update Tax:' . $tax->getId());
			$tax->setBillingAreaId($billingarea->getId());
			$tax->save();
		}
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 */
	private function migratePrice($shop)
	{
		$billingarea = $shop->getBillingAreas(0);
		if ($billingarea === null)
		{
			throw new Exception('Default Billingarea not found');
		}
		$query = "UPDATE `m_catalog_doc_price` SET `billingareaid` = " . $billingarea->getId() . ", `storewithtax` = 0, `value` = `valuewithouttax`, `valuewithoutdiscount` = `oldvaluewithouttax` WHERE `shopid` = " . $shop->getId();
		$result = $this->executeSQLQuery($query);	
		$this->log($result . ' Prices Updated');
	}
}