<?php


use ApacheSolrForTypo3\Solr\Tests\Unit\UnitTest;
use ApacheSolrForTypo3\Solr\Util;

class UtilTest extends UnitTest
{

    /**
     * @test
     */
    public function getConfigurationFromPageIdReturnsEmptyConfigurationForPageIdZero()
    {
        $configuration = Util::getConfigurationFromPageId(0, 'plugin.tx_solr', false, 0, false);
        $this->assertInstanceOf('ApacheSolrForTypo3\Solr\System\Configuration\TypoScriptConfiguration', $configuration);
    }

    /**
     * @return array
     */
    public function parseFloatDataProvider()
    {
        return [
            'simple' => ['in' => '3,5', 'expectedOut' => 3.5, 'locale' => 'de_DE'],
            'string_with_dot' => ['in' => '3.5', 'expectedOut' => 3.5, 'locale' => 'C'],
            'keepfloat' => ['in' => 3.5, 'expectedOut' => 3.5, 'locale' => 'de_DE'],
            'komma-value' => ['in' => "3", 'expectedOut' => 3.0, 'locale' => 'de_DE'],
            'integer' => ['in' => 3, 'expectedOut' => 3.0, 'locale' => 'de_DE']
        ];
    }

    /**
     *
     * @dataProvider parseFloatDataProvider
     * @test
     */
    public function canParseFloat($in, $expectedOut, $locale)
    {
        $currentLocale = setlocale(LC_NUMERIC, 0);
        $germanLocale = setlocale(LC_NUMERIC, $locale);

        if ($germanLocale === false) {
            $this->fail('Could not set local in test');
        }

        $out = Util::parseFloat($in);
        $this->assertSame($expectedOut, $out, 'Could not parse float');
        setlocale(LC_NUMERIC, $currentLocale);
    }
}
