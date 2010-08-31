<?php
class catalog_PHPTAL_CHANGE
{
	/**
	 * @param PHPTAL_Namespace_CHANGE $namespaceCHANGE
	 */
	public static function addAttributes($namespaceCHANGE)
	{
		$namespaceCHANGE->addAttribute(new PHPTAL_NamespaceAttributeSurround('productprice', 5));
		$namespaceCHANGE->addAttribute(new PHPTAL_NamespaceAttributeSurround('productvisual', 5));
	}
}
