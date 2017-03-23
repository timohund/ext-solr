<?php
namespace ApacheSolrForTypo3\Solr\Plugin\Results;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2015 Dimitri Ebert <dimitri.ebert@dkd.de>
 *  (c) 2012-2015 Ingo Renner <ingo@typo3.org>
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

use ApacheSolrForTypo3\Solr\Domain\Search\LastSearches\LastSearchesService;
use ApacheSolrForTypo3\Solr\Plugin\CommandPluginBase;
use ApacheSolrForTypo3\Solr\Plugin\PluginCommand;
use ApacheSolrForTypo3\Solr\System\Configuration\TypoScriptConfiguration;
use ApacheSolrForTypo3\Solr\Template;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Last searches view command to display a user's last searches or the last
 * searches of all users.
 *
 * @author Dimitri Ebert <dimitri.ebert@dkd.de>
 * @author Ingo Renner <ingo@typo3.org>
 */
class LastSearchesCommand implements PluginCommand
{

    /**
     * Parent plugin
     *
     * @var CommandPluginBase
     */
    protected $parentPlugin;

    /**
     * Configuration
     *
     * @var TypoScriptConfiguration
     */
    protected $configuration;

    /**
     * @var LastSearchesService
     */
    protected $lastSearchesService;

    /**
     * Constructor.
     *
     * @param CommandPluginBase $parentPlugin Parent plugin object.
     */
    public function __construct(CommandPluginBase $parentPlugin)
    {
        $this->parentPlugin = $parentPlugin;
        $this->configuration = $parentPlugin->typoScriptConfiguration;
    }

    /**
     * Provides the values for the markers for the last search links
     *
     * @return array an array containing values for markers for the last search links template
     */
    public function execute()
    {
        if (!$this->configuration->getSearchLastSearches()) {
            // command is not activated, intended early return
            return null;
        }

        $lastSearches = $this->getLastSearches();
        if (empty($lastSearches)) {
            return null;
        }

        $marker = [
            'loop_lastsearches|lastsearch' => $lastSearches
        ];

        return $marker;
    }

    /**
     * Prepares the content for the last search markers
     *
     * @return array An array with content for the last search markers
     */
    protected function getLastSearches()
    {
        /** @var $lastSearchesService \ApacheSolrForTypo3\Solr\Domain\Search\LastSearches\LastSearchesService */
        $lastSearchesService = GeneralUtility::makeInstance(LastSearchesService::class,
            $this->configuration,
            $GLOBALS['TSFE'],
            $GLOBALS['TYPO3_DB']);

            // fill array for output
        $lastSearches = [];
        $lastSearchesKeywords = $lastSearchesService->getLastSearches();
        foreach ($lastSearchesKeywords as $keywords) {
            $keywords = stripslashes($keywords);
            $lastSearches[] = [
                'q' => Template::escapeMarkers($keywords),
                'parameters' => '&q=' . urlencode($keywords),
                'pid' => $this->parentPlugin->getLinkTargetPageId()
            ];
        }

        return $lastSearches;
    }
}
