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
        'collection'=>'Collection of Sample Items',
        'atom:author'=>'Stuart Lewis',
        'atom:title'=>'If Sword is the Answer',
        'atom:summary'=>'The purpose of this paper is to describe the repository deposit protocol, Simple Web-service Offering Repository Deposit (SWORD), its development iteration, and some of its potential use cases. In addition, seven case studies of institutional use of SWORD are provided.',
        'atom:updated'=>'2013-05-15',
        'dc:abstract'=>"Simple abstract.",
        'dc:available'=>'2013',
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
        $resp = $sword->publish($this->metadata, array('resources/Sword.pdf','resources/example.zip'),
            array('application/pdf','application/zip'));
        $this->assertEquals(200,$resp->getStatusCode(), 'Publication Not Accepted! ' . $resp->getBody(true));
    }
}