<?php 

/**
 * This test uses the Sword test server running at the dspace demo server.
 * In order to be succesful, the server must be reachable from the client. 
 */
require_once('src/SwordService.php');
require_once('TestConfig.php');

class IntegrationTest extends PHPUnit_Framework_TestCase {

    function testConnectivity() {
        $sword = new SwordService(TestURL, TestUser, TestPass);
        $resp = $sword->service_document();
        $this->assertEquals(200,$resp->getStatusCode());
    }

    function testAtomDeposit() {
        $atomMetaJSON = '{' .
            '"atom:author" : "Stuart Lewis",'.
            '"atom:title" : "If sword is the Answer",'.
            '"atom:summary" : "The purpose of this paper is to describe the repository deposit protocol, Simple Web-service Offering Repository Deposit (SWORD), its development iteration, and some of its potential use cases. In addition, seven case studies of institutional use of SWORD are provided.",'.
            '"atom:updated" : "May 5, 2012",'.
            '"dc:abstract" : "Simple abstract",'.
            '"dc:available" : "2013",'.
            '"dc:accessRights" : "Open",'.
            '"dc:description" : "A PDF on Sword",'.
            '"dc:contributor" : "Stuart Lewis",'.
            '"dc:creator" : "Stuart Lewis",'.
            '"dc:format" : "PDF"'.
            '}';
        $sword = new SwordService(TestURL, TestUser, TestPass);
        $map = array(
            array('file'=>'resources/Sword.pdf', 'type'=>'application/pdf'),
            array('file'=>'resources/Example.zip','type'=>'application/zip',
                'package'=>'http://purl.org/net/sword/package/SimpleZip')
        );
        $resp = $sword->publishWithAtom('Collection of Sample Items', $atomMetaJSON, $map);
        $this->assertEquals(200,$resp->getStatusCode(), 'Publication Not Accepted! ' . $resp->getBody(true));
        /* Commented out due to lack of support for deleting with Sword2 */
        /*
        $eiri = $sword->discover_EIRI_ref($resp);
        $resp = $sword->delete($eiri);
        $this->assertEquals(204,$resp->getStatusCode(), 'Content not deleted !' . $resp->getBody(true));*/
    }

    function testMetsDeposit() {
        $sword = new SwordService(TestURL, TestUser, TestPass);
        $resp = $sword->publishWithMets('Collection of Sample Items', 'resources/example.zip');
        $this->assertEquals(201,$resp->getStatusCode(), 'Publication Not Accepted! ' . $resp->getBody(true));
        /* Commented out due to lack of support for deleting with Sword2 */
        /*
        $eiri = $sword->discover_EIRI_ref($resp);
        $resp = $sword->delete($eiri);
        $this->assertEquals(204,$resp->getStatusCode(), 'Content not deleted !' . $resp->getBody(true));*/
    }
}