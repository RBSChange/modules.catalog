<?php
/**
 * catalog_CompiledcrossitemService
 * @package modules.catalog
 */
class catalog_CompiledcrossitemService extends f_persistentdocument_DocumentService
{
	/**
	 * @var catalog_CompiledcrossitemService
	 */
	private static $instance;
	
	/**
	 * @return catalog_CompiledcrossitemService
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
	 * @return catalog_persistentdocument_compiledcrossitem
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_catalog/compiledcrossitem');
	}
	
	/**
	 * Create a query based on 'modules_catalog/compiledcrossitem' model.
	 * Return document that are instance of modules_catalog/compiledcrossitem,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_catalog/compiledcrossitem');
	}
	
	/**
	 * Create a query based on 'modules_catalog/compiledcrossitem' model.
	 * Only documents that are strictly instance of modules_catalog/compiledcrossitem
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_catalog/compiledcrossitem', false);
	}
	
	/**
	 * @param catalog_persistentdocument_product|catalog_persistentdocument_declinedproduct $product
	 */
	public function deleteForProduct($product)
	{
		$this->createQuery()->add(Restrictions::eq('targetId', $product->getId()))->delete();
		$this->createQuery()->add(Restrictions::eq('linkedId', $product->getId()))->delete();
	}
	
	/**
	 * @param catalog_persistentdocument_crossitem $generator
	 */
	public function deleteForGenerator($generator)
	{
		$this->createQuery()->add(Restrictions::eq('generatorId', $generator->getId()))->delete();
	}
	
	/**
	 * @param catalog_persistentdocument_crossitem $crossitem
	 */
	public function generateForCrossitem($crossitem)
	{
		if (!($crossitem instanceof catalog_persistentdocument_crossitem))
		{
			return;
		}
		
		if (!$crossitem->getTargetDocument() || !$crossitem->getLinkedDocument())
		{
			$crossitem->delete();
			return;
		}
		elseif (!$crossitem->isPublished())
		{
			$this->deleteForGenerator($crossitem);
			return;
		}
		
		try
		{
			$this->tm->beginTransaction();
			
			$idsToTarget = $this->getIdsToTarget($crossitem);
			$idsToLink = $this->getIdsToLink($crossitem);
			
			// Look for existing items.
			$toDelete = array();
			$toCheck = array();
			foreach ($this->createQuery()->add(Restrictions::eq('generatorId', $crossitem->getId()))->find() as $item)
			{
				/* @var $item catalog_persistentdocument_compiledcrossitem */
				if (in_array($item->getTargetId(), $idsToTarget) && in_array($item->getLinkedId(), $idsToLink))
				{
					$toCheck[$item->getTargetId()][$item->getLinkedId()] = $item;
				}
				elseif ($crossitem->getSymetrical() && in_array($item->getTargetId(), $idsToLink) && in_array($item->getLinkedId(), $idsToTarget))
				{
					$toCheck[$item->getTargetId()][$item->getLinkedId()] = $item;
				}
				else
				{
					$toDelete[] = $item;
				}
			}
			
			// Create missing items and check existing ones.
			foreach ($idsToTarget as $targetId)
			{
				foreach ($idsToLink as $linkedId)
				{
					$this->generateItem($targetId, $linkedId, $crossitem, $toDelete, $toCheck);
					if ($crossitem->getSymetrical())
					{
						$this->generateItem($linkedId, $targetId, $crossitem, $toDelete, $toCheck);
					}
				}
			}
			
			// Delete unused items.
			foreach ($toDelete as $item)
			{
				$item->delete();
			}
			
			catalog_CrossitemService::getInstance()->setCompiled($crossitem->getId());
			$this->tm->commit();
		}
		catch (Exception $e)
		{
			$this->tm->rollBack($e);
		}
	}
	
	/**
	 * @param catalog_persistentdocument_crossitem $crossitem
	 * @return integer[]
	 */
	protected function getIdsToTarget($crossitem)
	{
		$targetDoc = $crossitem->getTargetDocument();
		if ($targetDoc instanceof catalog_persistentdocument_product)
		{
			return array($targetDoc->getId());
		}
		elseif ($targetDoc instanceof catalog_persistentdocument_declinedproduct)
		{
			$query = catalog_ProductdeclinationService::getInstance()->createQuery()->add(Restrictions::eq('declinedproduct', $targetDoc));
			if ($crossitem->getTargetAxe1Value())
			{
				$query->add(Restrictions::eq('axe1', $crossitem->getTargetAxe1Value()));
			}
			if ($crossitem->getTargetAxe2Value())
			{
				$query->add(Restrictions::eq('axe2', $crossitem->getTargetAxe2Value()));
			}
			if ($crossitem->getTargetAxe3Value())
			{
				$query->add(Restrictions::eq('axe3', $crossitem->getTargetAxe3Value()));
			}
			return $query->setProjection(Projections::property('id'))->findColumn('id');
		}
		return array();
	}
	
	/**
	 * @param catalog_persistentdocument_crossitem $crossitem
	 * @return integer[]
	 */
	protected function getIdsToLink($crossitem)
	{
		$linkedDoc = $crossitem->getLinkedDocument();
		if ($linkedDoc instanceof catalog_persistentdocument_product)
		{
			return array($linkedDoc->getId());
		}
		elseif ($linkedDoc instanceof catalog_persistentdocument_declinedproduct)
		{
			$query = catalog_ProductdeclinationService::getInstance()->createQuery()->add(Restrictions::eq('declinedproduct', $linkedDoc));
			if ($crossitem->getLinkedAxe1Value())
			{
				$query->add(Restrictions::eq('axe1', $crossitem->getLinkedAxe1Value()));
			}
			if ($crossitem->getLinkedAxe2Value())
			{
				$query->add(Restrictions::eq('axe2', $crossitem->getLinkedAxe2Value()));
			}
			if ($crossitem->getLinkedAxe3Value())
			{
				$query->add(Restrictions::eq('axe3', $crossitem->getLinkedAxe3Value()));
			}
			return $query->setProjection(Projections::property('id'))->findColumn('id');
		}
		return array();
	}
	
	/**
	 * @param integer $targetId
	 * @param integer $linkedId
	 * @param catalog_persistentdocument_crossitem $crossitem
	 * @param catalog_persistentdocument_compiledcrossitem[] $toDelete
	 * @param array $toToCheck
	 */
	protected function generateItem($targetId, $linkedId, $crossitem, &$toDelete, $toToCheck)
	{
		if (isset($toToCheck[$targetId][$linkedId]))
		{
			$item = $toToCheck[$targetId][$linkedId];
		}
		else
		{
			if (count($toDelete))
			{
				$item = array_pop($toDelete);
			}
			else
			{
				$item = $this->getNewDocumentInstance();
				$item->setGeneratorId($crossitem->getId());
			}
			$item->setTargetId($targetId);
			$item->setLinkedId($linkedId);
			$item->setPosition($linkedId);
		}
		$item->setLinkType($crossitem->getLinkType());
		$this->save($item);
	}
	
	/**
	 * @param catalog_persistentdocument_product $target
	 * @param string $linkType
	 */
	public function getSortingInfosByTargetAndLinkType($target, $linkType)
	{
		$query = $this->createQuery()->add(Restrictions::eq('targetId', $target->getId()))->add(Restrictions::eq('linkType', $linkType));
		$ids = array();
		$infos = array();
		foreach ($query->addOrder(Order::asc('position'))->find() as $item)
		{
			/* @var $item catalog_persistentdocument_compiledcrossitem */
			$linked = $item->getLinkedIdInstance();
			if (in_array($linked->getId(), $ids))
			{
				continue;
			}
			$ids[] = $linked->getId();
			$infos[] = array('id' => $linked->getId(), 'label' => $linked->getTreeNodeLabel(), 
				'icon' => MediaHelper::getIcon($linked->getPersistentModel()->getIcon(), MediaHelper::SMALL));
		}
		return $infos;
	}
	
	/**
	 * @param catalog_persistentdocument_product $target
	 * @param string $linkType
	 * @param array $order
	 * @param boolean $applyToAllDeclinations
	 */
	public function setPositionsByTargetAndLinkType($target, $linkType, $order, $applyToAllDeclinations = false)
	{
		$tm = $this->getTransactionManager();
		try
		{
			$tm->beginTransaction();
			
			$targetIds = array();
			if ($applyToAllDeclinations && $target instanceof catalog_persistentdocument_productdeclination)
			{
				/* @var $target catalog_persistentdocument_productdeclination */
				foreach ($target->getDeclinations() as $declination)
				{
					$targetIds[] = $declination->getId();
				}
			}
			else
			{
				$targetIds[] = $target->getId();
			}
			
			$query = $this->createQuery()->add(Restrictions::in('targetId', $targetIds))->add(Restrictions::eq('linkType', $linkType));
			foreach ($query->find() as $doc)
			{
				/* @var $doc catalog_persistentdocument_compiledcrossitem */
				$docId = $doc->getLinkedId();
				if (isset($order[$docId]))
				{
					$doc->setPosition($order[$docId]);
					$doc->save();
				}
			}
			
			$tm->commit();
		}
		catch (Exception $e)
		{
			$tm->rollBack($e);
			throw $e;
		}
	}
	
	/**
	 * @param catalog_persistentdocument_product $target
	 * @param string $linkType
	 * @param catalog_persistentdocument_shop $shop
	 * @return integer[]
	 */
	public function getDisplayableLinkedIds($target, $linkType, $shop)
	{
		$query = $this->createQuery()->add(Restrictions::eq('targetId', $target->getId()))->add(Restrictions::eq('linkType', $linkType));
		$query->add(Restrictions::published());
		$criteria1 = $query->createPropertyCriteria('linkedId', 'modules_catalog/product')->add(Restrictions::published());
		$criteria2 = $criteria1->createCriteria('compiledproduct')->add(Restrictions::published())->add(Restrictions::eq('shopId', $shop->getId()))->add(Restrictions::eq('showInList', true));
		return $query->addOrder(Order::asc('position'))->setProjection(Projections::property('linkedId'))->findColumn('linkedId');
	}
	
	/**
	 * @param catalog_persistentdocument_compiledcrossitem $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
	protected function preSave($document, $parentNodeId)
	{
		$document->setLabel($document->getTargetId() . ' -> ' . $document->getLinkedId());
	}
}