<?php 

/**
 * This test uses the Sword test server running at the dspace demo server.
 * In order to be succesful, the server must be reachable from the client. 
 */
require_once('src/SwordService.php');
require_once('TestConfig.php');

class IntegrationTest extends PHPUnit_Framework_TestCase
{
    var $metadata = array (
        'author'=>'Stuart Lewis',
        'collection'=>'Collection of Sample Items',
        'title'=>'If Sword is the Answer',
        'updated'=>'2013-05-15',
        'dc:abstract'=>"Simple abstract.",
        'dc:available'=>'2013',
        'dc:contributor.author'=>'Lewis, Stuart',
        'dc:title'=>'If Sword is the Answer',
        'dc:type'=>'swordv2-test',
        'dc:accessRights'=>'Access Rights',
        'dc:alternative'=>'Alternative Title',
        'dc:available'=>'Date Available',
        'dc:bibliographicCitation'=>'Bibliographic Citation',
        'dc:contributor'=>'Contributor',
        'dc:description'=>'Description',
        'dc:hasPartHas'=>'Part',
        'dc:hasVersionHas'=>'Version',
        'dc:identifier'=>'Identifier',
        'dc:isPartOf'=>'Is Part Of',
        'dc:publisher'=>'Publisher',
        'dc:references'=>'References',
        'dc:rightsHolderRights'=>'Holder',
        'dc:source'=>'Source'
    );

    function testConnectivity() {
        $sword = new SwordService(TestURL, TestUser, TestPass);
        $resp = $sword->service_document();
        $this->assertEquals(200,$resp->getStatusCode());
    }

    function testDeposit() {
        $sword = new SwordService(TestURL, TestUser, TestPass);
        $resp = $sword->publish($this->metadata, array('resources/Sword.pdf','resources/example.zip',
            'resources/guzzle-schema-1.0.json'));
        $this->assertEquals(200,$resp->getStatusCode(), 'Publication Not Accepted! ' . $resp->getMessage());
    }

    function testMetsDeposit() {
        $sword = new SwordService(TestURL, TestUser, TestPass);
        $resp = $sword->publishMets($this->metadata['collection'], 'resources/example.zip');
        $this->assertEquals(200,$resp->getStatusCode(), 'Publication Not Accepted! ' . $resp->getMessage());
    }
}