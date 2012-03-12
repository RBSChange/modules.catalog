<?php
/**
 * catalog_patch_0369
 * @package modules.catalog
 */
class catalog_patch_0369 extends patch_BasePatch
{
 
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		if (!PatchService::getInstance()->isInstalled('catalog', '0368'))
		{
			throw new Exception('Please apply patch catalog 0368 before this!');
		}
		
		
		//<staticlist listid="modules_catalog/taxcode" label="&amp;modules.catalog.bo.general.Taxcode;">
		
		$list = list_ListService::getInstance()->getByListId('modules_catalog/taxcode');
		if ($list !== null)
		{
			$list->delete();
		}
		
		//<staticlist listid="modules_catalog/currencycode" label="&amp;modules.catalog.bo.general.Currency-code-list;"
		//	description="&amp;modules.catalog.bo.general.Currency-code-list-description;">
		$list = list_ListService::getInstance()->getByListId('modules_catalog/currencycode');
		if ($list !== null)
		{
			$list->delete();
		}
		
		//<dynamiclist listid="modules_catalog/shopcurrency" label="Devises avec code" />
		$list = list_ListService::getInstance()->getByListId('modules_catalog/shopcurrency');
		if ($list !== null)
		{
			$this->getPersistentProvider()->deleteDocument($list);
		}		
		
		
// 			DELETED modules_catalog/shop
// 		    <add name="currencyCode" type="String" min-occurs="1" from-list="modules_catalog/shopcurrency" default-value="EUR"/>
// 		    <add name="currencyPosition" type="String" min-occurs="1" localized="true" from-list="modules_catalog/currencyposition" default-value="right"/>
// 		    <add name="defaultTaxZone" type="String" min-occurs="1" db-size="20" from-list="modules_catalog/defaulttaxzones"/>
// 		    <add name="boTaxZone" type="String" db-size="20" from-list="modules_catalog/defaulttaxzones"/>
		$query = "ALTER TABLE `m_catalog_doc_shop` DROP `currencycode`, DROP `currencyposition`, DROP `defaulttaxzone`, DROP `botaxzone`";
		$this->executeSQLQuery($query);
		
		$query = "ALTER TABLE `m_catalog_doc_shop_i18n` DROP `currencyposition_i18n`";
		$this->executeSQLQuery($query);
				
// 			DELETED modules_catalog/price
//     		<add name="valueWithoutTax" type="Double" min-occurs="1"><constraints>min:0</constraints></add>
//     		<add name="oldValueWithoutTax" type="Double"><constraints>min:0</constraints></add>
//     		<add name="currencyId" type="Integer" min-occurs="1"/>		

		$query = "ALTER TABLE `m_catalog_doc_price` DROP `currencyid`, DROP `valuewithouttax`, DROP `oldvaluewithouttax`";
		$this->executeSQLQuery($query);
		
//			DELETE modules_catalog/tax
//     		<add name="shopId" type="Integer" min-occurs="1" />

		$query = "ALTER TABLE `m_catalog_doc_tax` DROP `shopid`";
		$this->executeSQLQuery($query);
	}
}