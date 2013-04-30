<?php

require_once('src/SwordService.php');

class AtomFromJSONTest extends PHPUnit_Framework_TestCase
{
    /* Tests the creation of an Atom XML file from a JSON string */
    public function testCreateAtom() { 
        $expected = '<?xml version="1.0"?>' .
            '<entry xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dcterms="http://purl.org/dc/terms">'. 
            '<title>Test Temporary Title</title>'. 
            '<author><name>Dr. Eager Beaver</name></author>'.
            '<id>facebook.com/EagerBeaver</id>'.
            '<summary>A discussion on mud.</summary>'. 
            '</entry>';
        
        $test_array = array (
            'filename'=>'resources/TestFile.pdf',
            'title'=>'Test Temporary Title',
            'author'=>'Dr. Eager Beaver',
            'id'=>'facebook.com/EagerBeaver',
            'summary'=>'A discussion on mud.'
        );   
        
        $sword = new SwordService('','','',''); 
        $actual = $sword->generate_atom($test_array);
        $this->assertXmlStringEqualsXmlString($expected,$actual,$actual);
    }

    /* Creates a 'extended' atom document with Dublin Core fields */
    public function testExtendedAtom() {
        $expected = '<?xml version="1.0"?>' .
            '<entry xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dcterms="http://purl.org/dc/terms">'. 
            '<title>Test Temporary Title</title>'. 
            '<author><name>Dr. Eager Beaver</name></author>'.
            '<id>facebook.com/EagerBeaver</id>'.
            '<summary>A discussion on mud.</summary>'.
            '<dcterms:abstract>Mud. A discussion in several parts.</dcterms:abstract>'.
            '<dcterms:available>2013</dcterms:available>'.
            '<dcterms:creator>Beaver, Dr. Eager</dcterms:creator>'.
            '<dcterms:title>Test Temporary Title</dcterms:title>'. 
            '</entry>';

        $test_array = array (
            'filename'=>'resources/TestFile.pdf',
            'title'=>'Test Temporary Title',
            'author'=>'Dr. Eager Beaver',
            'id'=>'facebook.com/EagerBeaver',
            'summary'=>'A discussion on mud.',
            'dcterms:abstract'=>'Mud. A discussion in several parts.',
            'dcterms:available'=>'2013',
            'dcterms:creator'=>'Beaver, Dr. Eager',
            'dcterms:title'=>'Test Temporary Title'             
        );

        $sword = new SwordService('','','','');
        $actual = $sword->generate_atom($test_array);
        $this->assertXmlStringEqualsXmlString($expected,$actual,$actual);
    }
}
?>