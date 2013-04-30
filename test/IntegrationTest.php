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
        'filename'=>'src/vendor/swordapp/swordappv2-php-library/test/test-files/atom_multipart/if-sword-is-the-answer.pdf',
        'title'=>'If Sword is the Answer',
        'author'=>'Stuart Lewis',
        'dcterms:abstract'=>"Purpose ‐ To describe the repository deposit protocol, Simple 
            Web‐service Offering Repository Deposit (SWORD), 
            its development iteration, and some of its potential use cases.
            In addition, seven case studies of institutional use of SWORD are provided. 
            Approach ‐ The paper describes the recent development cycle of the SWORD 
            standard, with issues being identified and overcome with a subsequent version. 
            Use cases and case studies of the new standard in action are included to 
            demonstrate the wide range of practical uses of the SWORD standard. 
            Implications ‐ SWORD has many potential use cases and has quickly become  
            the de facto standard for depositing items into repositories. By making 
            use of a widely‐supported interoperable standard, tools can be created that 
            start to overcome some of the problems of gathering content for deposit into 
            institutional repositories. They can do this by changing the submission process 
            from a ‘one‐size‐fits‐all’ solution, as provided by the repositorys own user 
            interface, to customised solutions for different users. 
            Originality ‐ Many of the case studies described in this paper are new and 
            unpublished, and describe methods of creating novel interoperable tools for 
            depositing items into repositories. The description of SWORD version 1.3 and its 
            development give an insight into the processes involved with the development of 
            a new standard.",
        'dcterms:available'=>'2013',
        'dcterms:creator'=>'Lewis, Stuart',
        'dcterms:title'=>'If Sword is the Answer',
        'dcterms:type'=>'swordv2-test'
    );

    public function testConnectivity() {
        $sword = new SwordService(TestURL, TestUser, TestPass);
        $sd = $sword->service_document();
        $this->assertEquals(200,$sd->sac_status);
    }

    public function testDeposit() {
        $sword = new SwordService(TestURL, TestUser, TestPass);
        $sd = $sword->service_document();
        $p_url = null;
        /* Seek the 'Public Collection' deposit URL */
        foreach ($sd->sac_workspaces as $workspace) {
            if ($p_url != null)
                break;
            $collections = $workspace->sac_collections;
            foreach ($collections as $collection) { 
                if ($collection->sac_colltitle == 'Public Collection') {
                    $p_url = $collection->sac_href;
                    break;
                }
            }
        }
        $this->assertNotNull($p_url,'Null publish URL!');
        $resp = $sword->publish($p_url, $this->metadata);
        $this->assertEquals(201,$resp->sac_status, 'Publication Not Accepted! ' . $resp->sac_statusmessage);
    }

    public function testUpdateDeposit() {
        $sword = new SwordService(TestURL, TestUser, TestPass);
    }

    public function testDeleteDeposit() {
        $sword = new SwordService(TestURL, TestUser, TestPass);
    }
}