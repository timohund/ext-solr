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

use ApacheSolrForTypo3\Solr\Domain\Search\FrequentSearches\FrequentSearchesService;
use ApacheSolrForTypo3\Solr\Plugin\CommandPluginBase;
use ApacheSolrForTypo3\Solr\Plugin\PluginCommand;
use ApacheSolrForTypo3\Solr\Template;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Command to list frequent searched terms.
 *
 * @author Dimitri Ebert <dimitri.ebert@dkd.de>
 */
class FrequentSearchesCommand implements PluginCommand
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
     * @var array
     */
    protected $frequentSearchConfiguration;

    /** @var \ApacheSolrForTypo3\Solr\Domain\Search\FrequentSearches\FrequentSearchesService */
    protected $frequentSearchesService;

    /**
     * @var bool
     */
    protected $isEnabled;

    /**
     * Constructor.
     *
     * @param CommandPluginBase $parentPlugin Parent plugin object.
     */
    public function __construct(CommandPluginBase $parentPlugin)
    {
        $this->parentPlugin = $parentPlugin;

        $this->isEnabled = $this->parentPlugin->typoScriptConfiguration->getSearchFrequentSearches();

            // if not enabled we can skip here
        if (!$this->isEnabled) {
            return null;
        }

        $configuration = $this->parentPlugin->typoScriptConfiguration;
        $this->frequentSearchConfiguration = $configuration->getSearchFrequentSearchesConfiguration();

        $cacheInstance = $this->getInitializeCache();

        $this->frequentSearchesService = GeneralUtility::makeInstance(FrequentSearchesService::class,
            $configuration,
            $cacheInstance,
            $GLOBALS['TSFE'],
            $GLOBALS['TYPO3_DB']

        );
    }

    /**
     * Initializes the cache for this command.
     *
     * @return FrontendInterface
     */
    protected function getInitializeCache()
    {
        /** @var $cacheManager CacheManager */
        $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
        return $cacheManager->getCache('tx_solr');
    }

    /**
     * Provides the values for the markers for the frequent searches links
     *
     * @return array An array containing values for markers for the frequent searches links template
     */
    public function execute()
    {
        if (!$this->isEnabled) {
            // command is not activated, intended early return
            return null;
        }

        $marker = [
            'loop_frequentsearches|term' => $this->getSearchTermMarkerProperties($this->frequentSearchesService->getFrequentSearchTerms())
        ];

        return $marker;
    }

    /**
     * Builds the properties for the frequent search term markers.
     *
     * @param array $frequentSearchTerms Frequent search terms as array with terms as keys and hits as the value
     * @return array An array with content for the frequent terms markers
     */
    protected function getSearchTermMarkerProperties(array $frequentSearchTerms)
    {
        $frequentSearches = [];

        $minimumSize = $this->frequentSearchConfiguration['minSize'];
        $maximumSize = $this->frequentSearchConfiguration['maxSize'];
        if (count($frequentSearchTerms)) {
            $maximumHits = max(array_values($frequentSearchTerms));
            $minimumHits = min(array_values($frequentSearchTerms));
            $spread = $maximumHits - $minimumHits;
            $step = ($spread == 0) ? 1 : ($maximumSize - $minimumSize) / $spread;

            foreach ($frequentSearchTerms as $term => $hits) {
                $size = round($minimumSize + (($hits - $minimumHits) * $step));
                $frequentSearches[] = [
                    'term' => Template::escapeMarkers($term),
                    'hits' => $hits,
                    'style' => 'font-size: ' . $size . 'px',
                    'class' => 'tx-solr-frequent-term-' . $size,
                    'parameters' => '&q=' . urlencode($term),
                    'pid' => $this->parentPlugin->getLinkTargetPageId()
                ];
            }
        }

        return $frequentSearches;
    }
}
