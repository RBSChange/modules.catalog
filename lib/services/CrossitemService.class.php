<?php
/**
 * catalog_CrossitemService
 * @package modules.catalog
 */
class catalog_CrossitemService extends f_persistentdocument_DocumentService
{
	/**
	 * @var catalog_CrossitemService
	 */
	private static $instance;

	/**
	 * @return catalog_CrossitemService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}

	/**
	 * @return catalog_persistentdocument_crossitem
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_catalog/crossitem');
	}

	/**
	 * Create a query based on 'modules_catalog/crossitem' model.
	 * Return document that are instance of modules_catalog/crossitem,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_catalog/crossitem');
	}
	
	/**
	 * Create a query based on 'modules_catalog/crossitem' model.
	 * Only documents that are strictly instance of modules_catalog/crossitem
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_catalog/crossitem', false);
	}
	
	/**
	 * @param catalog_persistentdocument_product|catalog_persistentdocument_declinedproduct $target
	 * @return array
	 */
	public function getCrossitemsInfosByTarget($target)
	{
		$data = array('id' => $target->getId(), 'isDeclined' => ($target instanceof catalog_persistentdocument_declinedproduct));
		
		$crossitems = $this->createQuery()->add(Restrictions::eq('targetDocument', $target))->add(Restrictions::isNotNull('linkedDocument'))->find();
		$data['targetCount'] = count($crossitems);
		$data['targetNodes'] = array();
		foreach ($crossitems as $crossitem)
		{
			$data['targetNodes'][] = $this->getCrossitemsInfos($crossitem);
		}
		
		$crossitems = $this->createQuery()->add(Restrictions::eq('linkedDocument', $target))->add(Restrictions::isNotNull('targetDocument'))->find();
		$data['linkedCount'] = count($crossitems);
		$data['linkedNodes'] = array();
		foreach ($crossitems as $crossitem)
		{
			$data['linkedNodes'][] = $this->getCrossitemsInfos($crossitem);
		}
		return $data;
	}
	
	/**
	 * @param catalog_persistentdocument_crossitem $crossitem
	 * @return array
	 */
	protected function getCrossitemsInfos($crossitem)
	{
		$ls = LocaleService::getInstance();
		$permissions = $this->getPermissions($crossitem, 'catalog');
		$status = $crossitem->getPublicationstatus();
		return array(
			'id' => $crossitem->getId(),
			'publicationstatus' => $status,
			'target' => $crossitem->getTargetDocument()->getTreeNodeLabel(),
			'linked' => $crossitem->getLinkedDocument()->getTreeNodeLabel(),
			'linkType' => $crossitem->getLinkTypeLabel(),
			'symetrical' => $ls->transBO('m.uixul.bo.general.' . ($crossitem->getSymetrical() ? 'yes' : 'no'), array('ucf')),
			'compiled' => $ls->transBO('m.uixul.bo.general.' . ($crossitem->getCompiled() ? 'yes' : 'no'), array('ucf')),
			'autoGenerated' => $ls->transBO('m.uixul.bo.general.' . ($crossitem->getAutoGenerated() ? 'yes' : 'no'), array('ucf')),
			'canedit' => isset($permissions['allpermissions']) || isset($permissions['Update']),
			'candelete' => isset($permissions['allpermissions']) || isset($permissions['Delete']),
			'canactivate' => (isset($permissions['allpermissions']) || isset($permissions['Activate'])) && ($status == 'DRAFT' || $status == 'CORRECTION'),
			'candeactivated' => (isset($permissions['allpermissions']) || isset($permissions['Deactivated'])) && ($status == 'ACTIVE' || $status == 'PUBLICATED'),
			'canreactivate' => (isset($permissions['allpermissions']) || isset($permissions['ReActivate'])) && ($status == 'DEACTIVATED'),
		);
	}
	
	/**
	 * @param catalog_persistentdocument_product|catalog_persistentdocument_declinedproduct $target
	 * @param integer[] $excludedIds
	 * @param string $feederClass
	 * @param integer $maxCount
	 * @return array
	 */
	public function getSuggestionInfos($target, $excludedIds, $feederClass, $maxCount)
	{
		$infos = array();
		foreach (catalog_CrossitemService::getInstance()->callFeederToGetProducts($target, $excludedIds, $feederClass, $maxCount) as $doc)
		{
			$infos[] = array(
				'id' => $doc->getId(),
				'label' => $doc->getLabel(),
				'codeReference' => $doc->getCodeReference(),
				'icon' => $doc->getPersistentModel()->getIcon()
			);
		}
		return $infos;
	}
	
	/**
	 * @param catalog_persistentdocument_crossitem $document
	 * @param boolean $compiled
	 */
	protected function updateCompiledProperty($document, $compiled)
	{
		if ($document !== null && $document->getCompiled() != $compiled)
		{
			if ($document->isModified())
			{
				Framework::warn(__METHOD__ . $document->__toString() . ", $compiled : not possible on modified crossitem");
				return;
			}
			if (Framework::isInfoEnabled())
			{
				Framework::info(__METHOD__ . ' ' . $document->__toString() . ($compiled ? ' is compiled' : ' to recompile'));
			}
			$document->setCompiled($compiled);
			$this->pp->updateDocument($document);
			f_DataCacheService::getInstance()->clearCacheByDocId($document->getId());
		}
	}
	
	/**
	 * @param integer $crossitemId
	 */
	public function setCompiled($crossitemId)
	{
		$crossitem = catalog_persistentdocument_crossitem::getInstanceById($crossitemId);
		$crossitem->getDocumentService()->updateCompiledProperty($crossitem, true);
	}
	
	/**
	 * @param integer[] $crossitemIds
	 */
	public function setNeedCompile($crossitemIds)
	{
		if (f_util_ArrayUtils::isNotEmpty($crossitemIds))
		{
			try
			{
				$this->tm->beginTransaction();
				foreach ($crossitemIds as $crossitemId)
				{
					$crossitem = catalog_persistentdocument_crossitem::getInstanceById($crossitemId);
					$crossitem->getDocumentService()->updateCompiledProperty($crossitem, false);
				}
				$this->tm->commit();
			}
			catch (Exception $e)
			{
				$this->tm->rollBack($e);
			}
		}
	}
	
	/**
	 * @param catalog_persistentdocument_product|catalog_persistentdocument_declinedproduct $document
	 */
	public function setNeedCompileByTargetOrLinkedDocument($document)
	{
		try
		{
			$this->tm->beginTransaction();
			
			$query = $this->createQuery()->add(Restrictions::orExp(
				Restrictions::eq('targetDocument', $document), 
				Restrictions::eq('linkedDocument', $document)
			));
			foreach ($query->add(Restrictions::eq('compiled', 'true'))->find() as $crossitem)
			{
				/* @var $crossitem catalog_persistentdocument_crossitem */ 
				$crossitem->getDocumentService()->updateCompiledProperty($crossitem, false);
			}
			$this->tm->commit();
		}
		catch (Exception $e)
		{
			$this->tm->rollBack($e);
		}
	}
	
	/**
	 * @param catalog_persistentdocument_product|catalog_persistentdocument_declinedproduct $target
	 * @param integer[] $linkedIds
	 * @param string $linkType
	 */
	public function createNewCrossitems($target, $linkedIds, $linkType)
	{
		foreach ($linkedIds as $linkedId)
		{
			$linked = DocumentHelper::getDocumentInstanceIfExists($linkedId);
			if (!$this->isValidForTargetOrLinkendDocument($linked))
			{
				continue;
			}
			
			$item = $this->getNewDocumentInstance();
			$item->setTargetDocument($target);
			$item->setLinkedDocument($linked);
			$item->setLinkType($linkType);
			$item->save();
		}
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return boolean
	 */
	public function isValidForTargetOrLinkendDocument($document)
	{
		if ($document instanceof catalog_persistentdocument_product && !($document instanceof catalog_persistentdocument_productdeclination))
		{
			return true;
		}
		return $document instanceof catalog_persistentdocument_declinedproduct;
	}
	
	/**
	 * @param catalog_persistentdocument_product|catalog_persistentdocument_declinedproduct $target
	 * @param string $linkType
	 * @param string $feederClass
	 * @param integer $maxCount
	 */
	public function refreshGeneratedItemsOnTarget($target, $linkType, $feederClass, $maxCount)
	{
		// Look in existing items.
		$excludedIds = array();
		$items = array();
		$query = $this->createQuery()->add(Restrictions::eq('targetDocument', $target))->add(Restrictions::eq('linkType', $linkType));
		$query->add(Restrictions::isNotNull('linkedDocument'));
		foreach ($query->find() as $item)
		{
			$linkedDoc = $item->getLinkedDocument();
			$linkedId = $linkedDoc->getId();
			if (!$item->getAutoGenerated() || $item->getPublicationstatus() == 'DEACTIVATED')
			{
				if (!in_array($linkedId, $excludedIds))
				{
					$excludedIds[] = $linkedId;
					if ($linkedDoc instanceof catalog_persistentdocument_declinedproduct)
					{
						foreach ($linkedDoc->getDeclinationArray() as $declination)
						{
							$excludeIds[] = $declination->getId();
						}
					}
				}
			}
			elseif (!isset($items[$linkedId]))
			{
				$items[$linkedId] = $item;
			}
			// If there are more than one generated item for the same linked document, delete exceding ones.
			// This is not supposed to occur...
			else 
			{
				$items[$item->getId()] = $item;
			}
		}
		
		// Call feeder to list items to create or delete.
		$toDelete = $items;
		$toGenerate = array();
		if ($feederClass && $feederClass != 'none' && $maxCount > 0)
		{
			$products = $this->callFeederToGetProducts($target, $excludedIds, $feederClass, $maxCount);
			foreach ($products as $productId => $product)
			{
				if (isset($items[$productId]))
				{
					unset($toDelete[$productId]);
				}
				elseif (!isset($excludedIds[$productId]))
				{
					$toGenerate[] = $product;
				}
			}
		}
		
		// Generate missing items, cycling items to delete if possible.
		foreach ($toGenerate as $linkedDoc)
		{
			$item = array_pop($toDelete);
			$item = $this->generateItem($target, $linkedDoc, $linkType, $item);
		}
		
		// Delete old items.
		foreach ($toDelete as $item)
		{
			$item->delete();
		}
	}
	
	/**
	 * @param catalog_persistentdocument_product|catalog_persistentdocument_declinedproduct $target
	 * @param catalog_persistentdocument_product|catalog_persistentdocument_declinedproduct $linked
	 * @param string $linkType
	 * @param catalog_persistentdocument_crossitem|null $item
	 */
	protected function generateItem($target, $linked, $linkType, $item = null)
	{
		if ($item instanceof catalog_persistentdocument_crossitem)
		{
			$item->setTargetAxe1Value(null);
			$item->setTargetAxe2Value(null);
			$item->setTargetAxe3Value(null);
			$item->setLinkedAxe1Value(null);
			$item->setLinkedAxe2Value(null);
			$item->setLinkedAxe3Value(null);
			$item->setSymetrical(false);
		}
		else
		{
			$item = $this->getNewDocumentInstance();
		}
		$item->setAutoGenerated(true);
		$item->setTargetDocument($target);
		$item->setLinkedDocument($linked);
		$item->setLinkType($linkType);
		$item->save();
	}
	
	/**
	 * @param catalog_persistentdocument_product|catalog_persistentdocument_declinedproduct $target
	 * @param integer[] $excludedIds
	 * @param string $feederClass
	 * @param integer $maxCount
	 * @return array<integer => catalog_persistentdocument_product|catalog_persistentdocument_declinedproduct>
	 */
	protected function callFeederToGetProducts($target, $excludedIds, $feederClass, $maxCount)
	{
		// Add the product itself and its declinations to excluded ids.
		if ($target instanceof catalog_persistentdocument_declinedproduct)
		{
			$targetDocs = $target->getDeclinationArray();
			foreach ($targetDocs as $declination)
			{
				$excludedIds[] = $declination->getId();
			}
			$targetDocs[] = $target;
		}
		else
		{
			$targetDocs = array($target);
		}
		$excludedIds[] = $target->getId();
		
		// Call the feeder for the product and for the declinations and merge results.
		$products = array();
		$feeder = f_util_ClassUtils::callMethod($feederClass, 'getInstance');
		foreach ($targetDocs as $targetDoc)
		{
			$parameters = array('productId' => $targetDoc->getId(), 'excludedId' => $excludedIds, 'maxResults' => $maxCount);
			foreach ($feeder->getProductArray($parameters) as $row)
			{
				$product = $row[0];
				if ($product instanceof catalog_persistentdocument_productdeclination)
				{
					$product = $product->getDeclinedproduct();
				}
				$products[$product->getId()] = $product;
			}
		}
		return $products;
	}
	
	/**
	 * @param catalog_persistentdocument_crossitem $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
	protected function preSave($document, $parentNodeId)
	{
		$label = $document->getTargetDocument() ? $document->getTargetDocument()->getId() : 'DELETED';
		$label .= ' ' . $document->getLinkType() . ' ';
		$label .= $document->getLinkedDocument() ? $document->getLinkedDocument()->getId() : 'DELETED';
		$document->setLabel($label);
	}
	
	/**
	 * @param integer $lastId
	 * @param integer $chunkSize
	 * @param boolean $onlyToCompile
	 * @return catalog_persistentdocument_crossitem[]
	 */
	public function getNextToCompile($lastId, $chunkSize, $onlyNotCompiled = true)
	{
		$query = $this->createQuery()->add(Restrictions::gt('id', $lastId));
		if ($onlyNotCompiled)
		{
			$query->add(Restrictions::eq('compiled', false));
		}
		return $query->addOrder(Order::asc('id'))->setMaxResults($chunkSize)->find();
	}
	
	/**
	 * @param integer $lastId
	 * @param integer $chunkSize
	 * @return catalog_persistentdocument_crossitem[]
	 */
	public function getNextGeneratedItems($lastId, $chunkSize)
	{
		$query = $this->createQuery()->add(Restrictions::gt('id', $lastId))->add(Restrictions::eq('autoGenerated', true));
		return $query->addOrder(Order::asc('id'))->setMaxResults($chunkSize)->find();
	}
	
	/**
	 * @param integer $lastId
	 * @param integer $chunkSize
	 * @param string $type 'product' or 'declined'
	 * @return catalog_persistentdocument_product[]|catalog_persistentdocument_declinedproduct[]
	 */
	public function getNextTargetsToGenerate($lastId, $chunkSize, $type)
	{
		if ($type == 'product')
		{
			$query = catalog_ProductService::getInstance()->createQuery();
		}
		elseif ($type == 'declined')
		{
			$query = catalog_DeclinedproductService::getInstance()->createQuery();
		}
		else 
		{
			return array();
		}
		return $query->add(Restrictions::gt('id', $lastId))->addOrder(Order::asc('id'))->setMaxResults($chunkSize)->find();
	}

	/**
	 * @param catalog_persistentdocument_crossitem $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function postSave($document, $parentNodeId)
	{
		// Generate compiledcrossitems.
		$this->updateCompiledProperty($document, false);
	}

	/**
	 * @param catalog_persistentdocument_crossitem $document
	 * @return void
	 */
	protected function preDelete($document)
	{
		// Delete compiledcrossitems.
		catalog_CompiledcrossitemService::getInstance()->deleteForGenerator($document);
	}
	/**
	 * @param catalog_persistentdocument_crossitem $document
	 * @param string $forModuleName
	 * @param array $allowedSections
	 * @return array
	 */
	public function getResume($document, $forModuleName, $allowedSections = null)
	{
		$resume = parent::getResume($document, $forModuleName, $allowedSections);
		
		$ls = LocaleService::getInstance();
		$resume['properties']['compiled'] = $ls->transBO('m.uixul.bo.general.' . ($document->getCompiled() ? 'yes' : 'no'), array('ucf'));
		$resume['properties']['autoGenerated'] = $ls->transBO('m.uixul.bo.general.' . ($document->getAutoGenerated() ? 'yes' : 'no'), array('ucf'));

		return $resume;
	}

	/**
	 * @param catalog_persistentdocument_crossitem $document
	 * @param string $forModuleName
	 * @return array
	 */
	protected function getPermissions($document, $forModuleName)
	{
		$permissions = parent::getPermissions($document, $forModuleName);
		// Auto generated crossitems should not be deleted from interface but just deactivated.
		if (isset($permissions['Delete']) && $document->getAutoGenerated())
		{
			unset($permissions['Delete']);
		}
		return $permissions;
	}
	
	/**
	 * @param catalog_persistentdocument_crossitem $document
	 * @param string[] $propertiesName
	 * @param array $datas
	 * @param integer $parentId
	 */
	public function addFormProperties($document, $propertiesName, &$datas, $parentId = null)
	{
		if ($document->isNew())
		{
			$target = DocumentHelper::getDocumentInstanceIfExists($parentId);
			if ($this->isValidForTargetOrLinkendDocument($target))
			{
				$document->setTargetDocument($target);
				$datas['targetDocument'] = $parentId;
			}
		}
		
		$datas['targetAxes'] = array();
		if ($document->getTargetDocument() instanceof catalog_persistentdocument_declinedproduct)
		{
			$datas['targetAxes'] = $this->getAxesInfosByDeclined($document->getTargetDocument());
		}
		
		$datas['linkedAxes'] = array();
		if ($document->getLinkedDocument() instanceof catalog_persistentdocument_declinedproduct)
		{
			$datas['linkedAxes'] = $this->getAxesInfosByDeclined($document->getLinkedDocument());
		}
		
		$datas['autoGenerated'] = $document->getAutoGenerated();
	}
	
	/**
	 * @param catalog_persistentdocument_declinedproduct $declined
	 * @return array
	 */
	public function getAxesInfosByDeclined($declined)
	{
		$datas = array();
		$axes = $declined->getDocumentService()->getAxes($declined);
		foreach ($axes as $index => $axe)
		{
			/* @var $axe catalog_ProductAxe */
			$datas['axe' . (1+$index) . 'Label'] = $axe->getLabel();
		}
			
		$declinations = $declined->getDeclinationArray();
		foreach ($declinations as $declination)
		{
			/* @var $declination catalog_persistentdocument_productdeclination */
			foreach ($axes as $index => $axe)
			{
				/* @var $axe catalog_ProductAxe */
				switch ($index)
				{
					case 0: $axeValue = $declination->getAxe1(); break;
					case 1: $axeValue = $declination->getAxe2(); break;
					case 2: $axeValue = $declination->getAxe3(); break;
				}
				$key = 'axe' . (1+$index) . 'Values';
				if (!isset($datas[$key][$axeValue]))
				{
					$datas[$key][$axeValue] = $axe->getAxeValueLabel($declination);
				}
			}
		}
		return $datas;
	}
}