<?php
/**
 * catalog_patch_0313
 * @package modules.catalog
 */
class catalog_patch_0313 extends patch_BasePatch
{

	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$this->execChangeCommand('update-autoload', array('modules/catalog'));
		$this->execChangeCommand('compile-documents', array());
		
		$newPath = f_util_FileUtils::buildWebeditPath('modules/catalog/persistentdocument/product.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'catalog', 'product');
		$newProp = $newModel->getPropertyByName('shippingModeId');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'product', $newProp);	

		
		$newPath = f_util_FileUtils::buildWebeditPath('modules/catalog/persistentdocument/virtualproduct.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'catalog', 'virtualproduct');
		$newProp = $newModel->getPropertyByName('secureMedia');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'virtualproduct', $newProp);
		
		
		$newPath = f_util_FileUtils::buildWebeditPath('modules/catalog/persistentdocument/shippingfilter.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'catalog', 'shippingfilter');
		$newProp = $newModel->getPropertyByName('selectbyproduct');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'shippingfilter', $newProp);
		
		if ($this->updateProjectConfig())
		{
			$this->execChangeCommand('compile-config', array());	
		}
		$this->execChangeCommand('compile-roles', array());	
		$this->execChangeCommand('compile-db-schema', array());
		$this->execChangeCommand('compile-editors-config', array()) ;
		$this->execChangeCommand('compile-locales', array('catalog'));	
		$this->execChangeCommand('compile-url-rewriting', array());	
	}

	private function updateProjectConfig()
	{
		$descDom = f_util_DOMUtils::fromPath(WEBEDIT_HOME."/config/project.xml");
		$pnl = $descDom->getElementsByTagName('config');
		if ($pnl->length == 0)
		{
			$pn = $descDom->appendChild($descDom->createElement('config'));	
		}
		else
		{
			$pn =  $pnl->item(0);
		}
		$pnl = $pn->getElementsByTagName('modules');
		if ($pnl->length == 0)
		{
			$pn = $pn->appendChild($descDom->createElement('modules'));	
		}
		else
		{
			$pn =  $pnl->item(0);
		}

		$pnl = $pn->getElementsByTagName('media');
		if ($pnl->length == 0)
		{
			$pn = $pn->appendChild($descDom->createElement('media'));	
		}
		else
		{
			$pn =  $pnl->item(0);
		}
		
		$pnl = $pn->getElementsByTagName('secureMediaStrategyClass');
		if ($pnl->length == 0)
		{
			$pn = $pn->appendChild($descDom->createElement('secureMediaStrategyClass'));	
		}
		else
		{
			$pn =  $pnl->item(0);
		}
		
		$addEntry = true;
		foreach ($pnl = $pn->getElementsByTagName('entry') as $entry) 
		{
			if ($entry->getAttribute('name') == 'virtualproduct')
			{
				$addEntry = false;
				break;
			}
		}
		
		if ($addEntry)
		{
			$entry = $pn->appendChild($descDom->createElement('entry'));	
			$entry->setAttribute('name', 'virtualproduct');
			$entry->appendChild($descDom->createTextNode('catalog_VirtualProductSecuremediaStrategy'));
			f_util_DOMUtils::save($descDom, WEBEDIT_HOME."/config/project.xml");
			$this->log('Add config/modules/media/secureMediaStrategyClass/entry[name=virtualproduct] in project.xml');
			return true;
		}
		return false;
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
		return '0313';
	}
}