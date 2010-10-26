<?php
/**
 * This class is here only to avoid fatal error after a migration for users who have this in session.
 * @deprecated use catalog_ProductList
 */
class catalog_SessionLists
{
	private $consultedProductIds;
	private $favoriteProductIds;
	private $comparedProductIds;
}