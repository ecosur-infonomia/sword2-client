<?php

require_once('src/SwordService.php');

class AtomFromJSONTest extends PHPUnit_Framework_TestCase
{
    /* Tests the creation of an Atom XML file from a JSON string */
    function testCreateAtom() {
        $expected = '<?xml version="1.0"?>' .
            '<atom:entry xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/terms" xmlns:mets="http://www.loc.gov/METS/">'.
            '<author><name>Dr. Eager Beaver</name></author>'.
            '<atom:title>Test Temporary Title</atom:title>'.
            '<atom:id>facebook.com/EagerBeaver</atom:id>'.
            '<atom:summary>A discussion on mud.</atom:summary>'.
            '</atom:entry>';
        
        $test_array = array (
            'author'=>'Dr. Eager Beaver',
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
            '<atom:entry xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/terms" xmlns:mets="http://www.loc.gov/METS/">'.
            '<author><name>Dr. Eager Beaver</name></author>'.
            '<atom:title>Test Temporary Title</atom:title>'.
            '<atom:id>facebook.com/EagerBeaver</atom:id>'.
            '<atom:summary>A discussion on mud.</atom:summary>'.
            '<dc:abstract>Mud. A discussion in several parts.</dc:abstract>'.
            '<dc:available>2013</dc:available>'.
            '<dc:creator>Beaver, Dr. Eager</dc:creator>'.
            '<dc:title>Test Temporary Title</dc:title>'. 
            '</atom:entry>';

        $test_array = array (
            'author'=>'Dr. Eager Beaver',
            'atom:title'=>'Test Temporary Title',
            'atom:id'=>'facebook.com/EagerBeaver',
            'atom:summary'=>'A discussion on mud.',
            'dc:abstract'=>'Mud. A discussion in several parts.',
            'dc:available'=>'2013',
            'dc:creator'=>'Beaver, Dr. Eager',
            'dc:title'=>'Test Temporary Title'             
        );

        $sword = new SwordService('','','','');
        $actual = $sword->generateAtom($test_array);
        $this->assertXmlStringEqualsXmlString($expected,$actual,$actual);
    }
}
?>