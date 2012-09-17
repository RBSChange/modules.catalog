<?php
interface catalog_FacetIndexerStrategy
{
	/**
	 * @param indexer_IndexedDocument $indexDocument
	 * @param catalog_persistentdocument_compiledproduct $compiledProduct
	 * @return boolean
	 */
	public function populateIndexDocument($indexDocument, $compiledProduct);
}