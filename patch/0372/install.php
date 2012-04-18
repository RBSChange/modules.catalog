<?php
/**
 * catalog_patch_0372
 * @package modules.catalog
 */
class catalog_patch_0372 extends patch_BasePatch
{
	
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$newPath = f_util_FileUtils::buildWebeditPath('modules/catalog/persistentdocument/paymentfilter.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'catalog', 'paymentfilter');
		$newProp = $newModel->getPropertyByName('billingArea');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'paymentfilter', $newProp);
		
		$newPath = f_util_FileUtils::buildWebeditPath('modules/catalog/persistentdocument/shippingfilter.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'catalog', 'shippingfilter');
		$newProp = $newModel->getPropertyByName('billingArea');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'shippingfilter', $newProp);
			
		$this->execChangeCommand('compile-db-schema');
		
		$this->execChangeCommand('compile-locales', array('catalog'));
		
		$this->executeModuleScript('list-billingareabyshop.xml', 'catalog');
		
		$array = catalog_PaymentfilterService::getInstance()->createQuery()->find();
		foreach ($array as $pf)
		{
			/* @var $pf catalog_persistentdocument_paymentfilter */
			if ($pf->getBillingArea() === null)
			{
				if ($pf->getShop() === null)
				{
					$this->logWarning('Invalid Paymentfilter ' . $pf->getId() . ' - ' . $pf->getLabel());
				}
				else
				{
					$pf->setBillingArea($pf->getShop()->getDefaultBillingArea());
					$pf->save();
				}
			}
		}
		
		$array = catalog_ShippingfilterService::getInstance()->createQuery()->find();
		foreach ($array as $sf)
		{
			/* @var $sf catalog_persistentdocument_shippingfilter */
			if ($sf->getBillingArea() === null)
			{
				if ($sf->getShop() === null)
				{
					$this->logWarning('Invalid Shippingfilter ' . $sf->getId() . ' - ' . $sf->getLabel());
				}
				else
				{
					$sf->setBillingArea($sf->getShop()->getDefaultBillingArea());
					$sf->save();
				}
			}
		}
	}
}