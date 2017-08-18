<?php
namespace ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\Options;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
*/

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\DefaultFacetQueryBuilder;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\FacetQueryBuilderInterface;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\SortingExpression;
use ApacheSolrForTypo3\Solr\System\Configuration\TypoScriptConfiguration;

class OptionsFacetQueryBuilder extends DefaultFacetQueryBuilder implements FacetQueryBuilderInterface {

    /**
     * @param string $facetName
     * @param TypoScriptConfiguration $configuration
     * @return array
     */
    public function build($facetName, TypoScriptConfiguration $configuration)
    {
        $facetParameters = [];
        $facetConfiguration = $configuration->getSearchFacetingFacetByName($facetName);

        if (!$facetConfiguration['useJson']) {
            return parent::build($facetName, $configuration);
        }

        $jsonFacetOptions = [
            'type' => 'terms',
            'field' => $facetConfiguration['field'],
            'limit' => $facetConfiguration['limit'] > 0 ? (int)$facetConfiguration['limit'] : $configuration->getSearchFacetingFacetLimit(),
            'mincount' => $facetConfiguration['mincount'] > 0 ? (int)$facetConfiguration['mincount'] : $configuration->getSearchFacetingMinimumCount(),
        ];

        if (isset($facetConfiguration['sortBy'])) {
            $sortingExpression = new SortingExpression();
            $sorting = $facetConfiguration['sortBy'];
            $direction = $facetConfiguration['sortDirection'];
            $jsonFacetOptions['sort'] = $sortingExpression->getForJsonFacet($sorting, $direction);
        }

        $isKeepAllOptionsActiveForSingleFacet = $facetConfiguration['keepAllOptionsOnSelection'] == 1;
        $isKeepAllOptionsActiveGlobalsAndCountsEnabled = $configuration->getSearchFacetingKeepAllFacetsOnSelection()
            && $configuration->getSearchFacetingCountAllFacetsForSelection();

        if ($isKeepAllOptionsActiveForSingleFacet || $isKeepAllOptionsActiveGlobalsAndCountsEnabled) {
            $jsonFacetOptions['domain']['excludeTags'] = $facetConfiguration['field'];
        } else {
            // keepAllOptionsOnSelection globally active
            $facets = [];
            foreach ($configuration->getSearchFacetingFacets() as $facet) {
                $facets[] = $facet['field'];
            }
            $jsonFacetOptions['domain']['excludeTags'] = implode(',', $facets);
        }

        $facetParameters['json.facet'][$facetConfiguration['field']] = $jsonFacetOptions;
        return $facetParameters;
    }
}
