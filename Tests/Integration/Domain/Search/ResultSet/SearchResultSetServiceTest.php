<?php

namespace ApacheSolrForTypo3\Solr\Tests\Integration\Domain\Search\ResultSet;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2015 Timo Schmidt <timo.schmidt@dkd.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use ApacheSolrForTypo3\Solr\Domain\Search\SearchRequest;
use ApacheSolrForTypo3\Solr\Tests\Integration\IntegrationTest;
use ApacheSolrForTypo3\Solr\Util;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SearchResultSetServiceTest extends IntegrationTest
{

    /**
     * Executed after each test. Emptys solr and checks if the index is empty
     */
    public function tearDown()
    {
        $this->cleanUpSolrServerAndAssertEmpty();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function canGetDocumentById()
    {
        // trigger a search
        $this->indexPageIdsFromFixture('can_get_searchResultSet.xml', [1, 2, 3, 4, 5]);

        $this->waitToBeVisibleInSolr();

        $solrContent = file_get_contents('http://localhost:8983/solr/core_en/select?q=*:*');
        $this->assertContains('b8c8d04e66c58f01283ef81a4ded197f26ab402a/pages/1/0/0/0', $solrContent);

        $solrConnection = GeneralUtility::makeInstance('ApacheSolrForTypo3\\Solr\\ConnectionManager')
            ->getConnectionByPageId(1, 0, 0);

        $typoScriptConfiguration = Util::getSolrConfiguration();

        $search = GeneralUtility::makeInstance('ApacheSolrForTypo3\\Solr\\Search', $solrConnection);
        /** @var $searchResultsSetService \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResultSetService */
        $searchResultsSetService = GeneralUtility::makeInstance('ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResultSetService', $typoScriptConfiguration, $search);
        $document = $searchResultsSetService->getDocumentById('b8c8d04e66c58f01283ef81a4ded197f26ab402a/pages/1/0/0/0');

        $this->assertSame($document->getTitle(), 'Products', 'Could not get document from solr by id');
    }


    /**
     * @test
     */
    public function canGetVariants()
    {

        $this->indexPageIdsFromFixture('can_get_searchResultSet.xml', [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);

        $this->waitToBeVisibleInSolr();
        $solrConnection = GeneralUtility::makeInstance('ApacheSolrForTypo3\\Solr\\ConnectionManager')
            ->getConnectionByPageId(1, 0, 0);

        $typoScriptConfiguration = Util::getSolrConfiguration();
        $typoScriptConfiguration->mergeSolrConfiguration([
           'search.' =>[
               'variants' => 1,
               'variants.' => [
                   'variantField' => 'pid',
                   'expand' => 1,
                   'limit' => 11
               ]
           ]
        ]);

        $this->assertTrue($typoScriptConfiguration->getSearchVariants(), 'Variants are not enabled');
        $this->assertEquals('pid', $typoScriptConfiguration->getSearchVariantsField());
        $this->assertEquals(11, $typoScriptConfiguration->getSearchVariantsLimit());

        $search = GeneralUtility::makeInstance('ApacheSolrForTypo3\\Solr\\Search', $solrConnection);
        /** @var $searchResultsSetService \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResultSetService */
        $searchResultsSetService = GeneralUtility::makeInstance('ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResultSetService', $typoScriptConfiguration, $search);

        /** @var $searchRequest SearchRequest */
        $searchRequest = GeneralUtility::makeInstance(SearchRequest::class);
        $searchRequest->setRawQueryString('*');

        $searchResultSet = $searchResultsSetService->search($searchRequest);

        $searchResults = $searchResultSet->getSearchResults();
        $this->assertSame(3, count($searchResults), 'There should be three results at all');

        // We assume that the first result has one variants.
        $firstResult = $searchResults[0];
        $this->assertSame(1, count($firstResult->getVariants()));

        $secondResult = $searchResults[1];
        $this->assertSame(3, count($secondResult->getVariants()));
        $this->assertSame('Men Socks', $secondResult->getTitle());


        // And every variant is indicated to be a variant.
        foreach ($firstResult->getVariants() as $variant) {
            $this->assertTrue($variant->getIsVariant(), 'Document should be a variant');
        }
    }
}
