<?php

namespace ApacheSolrForTypo3\Solr\Tests\Integration\Domain\Index;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Timo Schmidt <timo.schmidt@dkd.de>
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


use ApacheSolrForTypo3\Solr\Domain\Index\Queue\RecordMonitor\Helper\RootPageResolver;
use ApacheSolrForTypo3\Solr\Tests\Integration\IntegrationTest;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Testcase for the root page resolver
 *
 * @author Timo Hund
 */
class RootPageResolverTest extends IntegrationTest
{
    /**
     * @test
     */
    public function canResolveNestedRootPage()
    {
        $this->importDataSetFromFixture('can_get_root_page_when_nested.xml');
        /** @var $rootPageResolver RootPageResolver */
        $rootPageResolver = GeneralUtility::makeInstance(RootPageResolver::class);
        $rootPageId = $rootPageResolver->getRootPageId(3);
        $this->assertSame(2, $rootPageId, 'Could not get expected root page for nested root page');
    }

}