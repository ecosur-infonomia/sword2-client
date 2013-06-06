<?php

require_once('src/Metadata.php');

class MetadataTest extends PHPUnit_Framework_TestCase
{
    /* Tests the creation of an Atom XML file from a JSON string */
    function testCreateAtom() {
        $expected = '<?xml version="1.0"?>' .
            '<atom:entry xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dcterms="http://purl.org/dc/terms/" '.
                'xmlns:dc="http://dublincore.org/documents/dcmi-terms/" '.
                'xmlns:marc="http://dublincore.org/documents/marc-terms/">'.
            '<atom:author>Dr. Eager Beaver</atom:author>'.
            '<atom:title>Test Temporary Title</atom:title>'.
            '<atom:id>facebook.com/EagerBeaver</atom:id>'.
            '<atom:summary>A discussion on mud.</atom:summary>'.
            '</atom:entry>';
        
        $test_json = '{ ' .
            '"atom:author" : "Dr. Eager Beaver",' .
            '"atom:title" : "Test Temporary Title", ' .
            '"atom:id" : "facebook.com/EagerBeaver",' .
            '"atom:summary" : "A discussion on mud." }';

        $meta = new Metadata($test_json);
        $actual = $meta->generateAtom();
        $this->assertXmlStringEqualsXmlString($expected,$actual);
    }

    /* Creates a 'extended' atom document with Dublin Core fields */
    function testExtendedAtom() {
        $expected = '<?xml version="1.0"?>' .
            '<atom:entry xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dcterms="http://purl.org/dc/terms/" '.
                'xmlns:dc="http://dublincore.org/documents/dcmi-terms/" '.
                'xmlns:marc="http://dublincore.org/documents/marc-terms/">'.
            '<atom:author>Dr. Eager Beaver</atom:author>'.
            '<atom:title>Test Temporary Title</atom:title>'.
            '<atom:id>facebook.com/EagerBeaver</atom:id>'.
            '<atom:summary>A discussion on mud.</atom:summary>'.
            '<dcterms:abstract>Mud. A discussion in several parts.</dcterms:abstract>'.
            '<dcterms:available>2013</dcterms:available>'.
            '<dcterms:creator>Beaver, Dr. Eager</dcterms:creator>'.
            '<dcterms:title>Test Temporary Title</dcterms:title>'.
            '</atom:entry>';

        $test_json = '{' .
            '"atom:author" : "Dr. Eager Beaver", ' .
            '"atom:title" : "Test Temporary Title", '.
            '"atom:id" : "facebook.com/EagerBeaver", '.
            '"atom:summary" : "A discussion on mud.", '.
            '"dcterms:abstract": "Mud. A discussion in several parts.", '.
            '"dcterms:available" : "2013", '.
            '"dcterms:creator" : "Beaver, Dr. Eager", '.
            '"dcterms:title" : "Test Temporary Title" '.
            '}';

        $meta = new Metadata($test_json);
        $actual = $meta->generateAtom();
        $this->assertXmlStringEqualsXmlString($expected,$actual);
    }
}
