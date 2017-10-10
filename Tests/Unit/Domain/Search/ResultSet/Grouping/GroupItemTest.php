<?php
namespace ApacheSolrForTypo3\Solr\Tests\Unit\Domain\Search\ResultSet\Grouping;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 Timo Hund <timo.hund@dkd.de>
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

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Grouping\Group;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Grouping\GroupItem;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Result\SearchResult;
use ApacheSolrForTypo3\Solr\Tests\Unit\UnitTest;

/**
 * Unit test case for the Group class
 *
 * @author Timo Hund <timo.hund@dkd.de>
 */
class GroupItemTest extends UnitTest
{

    /**
     * @var GroupItem
     */
    protected $groupItem;

    /**
     * @var Group
     */
    protected $parentGroup;

    /**
     * @return void
     */
    public function setUp()
    {
        $this->parentGroup = new Group('typeGroup');
        $this->groupItem = new GroupItem($this->parentGroup, 'pages', 12, 1, 99);
    }

    /**
     * @test
     */
    public function canGetMaxScore()
    {
        $this->assertSame(99, $this->groupItem->getMaxScore(), 'Unexpected maxScore');
    }

    /**
     * @test
     */
    public function canGetStart()
    {
        $this->assertSame(1, $this->groupItem->getStart(), 'Unexpected start');
    }

    /**
     * @test
     */
    public function canGetNumFound()
    {
        $this->assertSame(12, $this->groupItem->getNumFound(), 'Unexpected numFound');
    }

    /**
     * @test
     */
    public function canGetGroupValue()
    {
        $this->assertSame('pages', $this->groupItem->getGroupValue(), 'Unexpected groupValue');
    }

    /**
     * @test
     */
    public function canGetGroup()
    {
        $this->assertSame($this->parentGroup, $this->groupItem->getGroup(), 'Unexpected parentGroup');
    }

    /**
     * @test
     */
    public function canGetSearchResults()
    {
        $this->assertSame(0, $this->groupItem->getSearchResults()->getCount());

        $searchResult = $this->getDumbMock(SearchResult::class);
        $this->groupItem->addSearchResult($searchResult);

        $this->assertSame(1, $this->groupItem->getSearchResults()->getCount());
    }
}