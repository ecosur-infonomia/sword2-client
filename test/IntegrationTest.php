<?php 

/**
 * This test uses the Sword test server running at the dspace demo server.
 * In order to be succesful, the server must be reachable from the client. 
 */
require_once('src/SwordService.php');
require_once('TestConfig.php');

class IntegrationTest extends PHPUnit_Framework_TestCase {

    var $atomMetadata = array (
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
        'dc:source'=>'Source',
        'dc:format'=>'PDF'
    );

    var $metsMetadata = array(

    );

    function testConnectivity() {
        $sword = new SwordService(TestURL, TestUser, TestPass);
        $resp = $sword->service_document();
        $this->assertEquals(200,$resp->getStatusCode());
    }

    /*
    function testDeposit() {
        $sword = new SwordService(TestURL, TestUser, TestPass);
        $map = array(
            array('file'=>'resources/Sword.pdf', 'type'=>'application/pdf'),
            array('file'=>'resources/Example.zip','type'=>'application/zip',
                'package'=>'http://purl.org/net/sword/package/SimpleZip')
        );
        $resp = $sword->publishWithAtom('Collection of Sample Items', $this->atomMetadata, $map);
        $this->assertEquals(200,$resp->getStatusCode(), 'Publication Not Accepted! ' . $resp->getBody(true));
    }
    */

    function testMetsDeposit() {
        $sword = new SwordService(TestURL, TestUser, TestPass);
        $resp = $sword->publishWithMets('Collection of Sample Items', 'resources/example.zip');
        $this->assertEquals(200,$resp->getStatusCode(), 'Publication Not Accepted! ' . $resp->getBody(true));
    }
}