<?php
/**
 * @date Thu, 13 Dec 2007 16:12:23 +0100
 * @author intportg
 */
class catalog_ProductlistService extends f_persistentdocument_DocumentService
{
	/**
	 * @var catalog_ProductlistService
	 */
	private static $instance;

	/**
	 * @return catalog_ProductlistService
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
	 * @return catalog_persistentdocument_productlist
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_catalog/productlist');
	}

	/**
	 * Create a query based on 'modules_catalog/productlist' model
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_catalog/productlist');
	}

	/**
	 * Return a list if a front end user is logged
	 * @return catalog_persistentdocument_productlist | null
	 */
	public function getCurrentProductList($name = 'favorite')
	{
		$user = users_UserService::getInstance()->getCurrentFrontEndUser();
		if ($user)
		{
			return $this->getByUserAndListName($user, $name);
		}
		return null;
	}
	
	/**
	 * @param users_persistentdocument_user $user
	 * @param string $name
	 * @return catalog_persistentdocument_productlist
	 */
	private function getByUserAndListName($user, $name)
	{
		$list = $this->createQuery()->add(Restrictions::eq('user', $user))->add(Restrictions::eq('listName', $name))->findUnique();
		if ($list === null)
		{
			$list = $this->getNewDocumentInstance();
			$list->setLabel($user->getFullname());
			$list->setUser($user);
			$list->setListName($name);
		}
		return $list;
	}
}