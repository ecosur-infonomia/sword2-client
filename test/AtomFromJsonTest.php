<?php

require_once('src/SwordService.php');

class AtomFromJSONTest extends PHPUnit_Framework_TestCase
{
    /* Tests the creation of an Atom XML file from a JSON string */
    function testCreateAtom() {
        $expected = '<?xml version="1.0"?>' .
            '<atom:entry xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dcterms="http://purl.org/dc/terms" xmlns:mets="http://www.loc.gov/METS/">'.
            '<atom:author><atom:name>Dr. Eager Beaver</atom:name></atom:author>'.
            '<atom:title>Test Temporary Title</atom:title>'.
            '<atom:id>facebook.com/EagerBeaver</atom:id>'.
            '<atom:summary>A discussion on mud.</atom:summary>'.
            '</atom:entry>';
        
        $test_array = array (
            'atom:author'=>'Dr. Eager Beaver',
            'atom:title'=>'Test Temporary Title',
            'atom:id'=>'facebook.com/EagerBeaver',
            'atom:summary'=>'A discussion on mud.'
        );   
        
        $sword = new SwordService('','','',''); 
        $actual = $sword->generateAtom($test_array);
        $this->assertXmlStringEqualsXmlString($expected,$actual,$actual);
    }

    /* Creates a 'extended' atom document with Dublin Core fields */
    function testExtendedAtom() {
        $expected = '<?xml version="1.0"?>' .
            '<atom:entry xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dcterms="http://purl.org/dc/terms" xmlns:mets="http://www.loc.gov/METS/">'.
            '<atom:author><atom:name>Dr. Eager Beaver</atom:name></atom:author>'.
            '<atom:title>Test Temporary Title</atom:title>'.
            '<atom:id>facebook.com/EagerBeaver</atom:id>'.
            '<atom:summary>A discussion on mud.</atom:summary>'.
            '<dcterms:abstract>Mud. A discussion in several parts.</dcterms:abstract>'.
            '<dcterms:available>2013</dcterms:available>'.
            '<dcterms:creator>Beaver, Dr. Eager</dcterms:creator>'.
            '<dcterms:title>Test Temporary Title</dcterms:title>'.
            '</atom:entry>';

        $test_array = array (
            'atom:author'=>'Dr. Eager Beaver',
            'atom:title'=>'Test Temporary Title',
            'atom:id'=>'facebook.com/EagerBeaver',
            'atom:summary'=>'A discussion on mud.',
            'dcterms:abstract'=>'Mud. A discussion in several parts.',
            'dcterms:available'=>'2013',
            'dcterms:creator'=>'Beaver, Dr. Eager',
            'dcterms:title'=>'Test Temporary Title'
        );

        $sword = new SwordService('','','','');
        $actual = $sword->generateAtom($test_array);
        $this->assertXmlStringEqualsXmlString($expected,$actual,$actual);
    }
}
?>