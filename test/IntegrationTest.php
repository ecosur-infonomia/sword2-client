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
        'title'=>'If Sword is the Answer',
        'updated'=>'2013-05-15',
        'author'=>'Stuart Lewis',
        'abstract'=>'Simple abstract.',
        'dcterms:abstract'=>"Simple abstract.",
        'dcterms:available'=>'2013',
        'dcterms:creator'=>'Lewis, Stuart',
        'dcterms:title'=>'If Sword is the Answer',
        'dcterms:type'=>'swordv2-test',
        'dcterms:accessRights'=>'Access Rights',
        'dcterms:alternative'=>'Alternative Title',
        'dcterms:available'=>'Date Available',
        'dcterms:bibliographicCitation'=>'Bibliographic Citation',
        'dcterms:contributor'=>'Contributor',
        'dcterms:description'=>'Description',
        'dcterms:hasPartHas'=>'Part',
        'dcterms:hasVersionHas'=>'Version',
        'dcterms:identifier'=>'Identifier',
        'dcterms:isPartOf'=>'Is Part Of',
        'dcterms:publisher'=>'Publisher',
        'dcterms:references'=>'References',
        'dcterms:rightsHolderRights'=>'Holder',
        'dcterms:source'=>'Source'
    );

    function testConnectivity() {
        $sword = new SwordService(TestURL, TestUser, TestPass);
        $resp = $sword->service_document();
        $this->assertEquals(200,$resp->getStatusCode());
    }

    function testSimpleDeposit() {
        $sword = new SwordService(TestURL, TestUser, TestPass);
        $metadata = array(
          'collection'=>'Collection of Sample Items',
          'title'=>'Title'
        );
        $resp = $sword->publish($metadata, array(
            'src/vendor/swordappv2-php-library/test/test-files/atom_multipart/if-sword-is-the-answer.pdf'
        ));
        $this->assertEquals(201,$resp->getStatusCode(), 'Publication Not Accepted! ' . $resp->getMessage());
    }

    function testDeposit() {
        $sword = new SwordService(TestURL, TestUser, TestPass);
        $resp = $sword->publish($this->metadata, array(
            'src/vendor/swordappv2-php-library/test/test-files/atom_multipart/if-sword-is-the-answer.pdf'
        ));
        $this->assertEquals(201,$resp->getStatusCode(), 'Publication Not Accepted! ' . $resp->getMessage());
    }
}