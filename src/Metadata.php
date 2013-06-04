<?php
/**  Copyright 2013 ECOSUR and Andrew Waterman **/

/**
 * Metadata.php
 *
 * Handles parsing and manipulation of Metadata from an
 * instantiating JSON string.
 *
 * Author: "Andrew G. Waterman" <awaterma@ecosur.mx>
 **/
require 'vendor/autoload.php';

class Metadata {

    var $json;

    var $parsed;

    var $filter;

    function __construct($JSONString, $filtered = null)
    {
        $this->json = $JSONString;
        $this->filter = array('collection');
        if ($filtered != null) {
            foreach ($filtered as $key) {
                $this->filter->push($key);
            }
        }
    }

    function parse() {
        if ($this->parsed == null) {
            $this->parsed = json_decode($this->json, true);
        }

        return $this->parsed;
    }

    /* Generates an ATOM document from a given JSON dictionary */
    function generateAtom()
    {
        $writer = new XMLWriter;
        $writer->openMemory();
        $writer->startDocument('1.0');

        /* Atom Entry */
        $writer->startElement('atom:entry');
        $writer->writeAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
        $writer->writeAttribute('xmlns:dcterms', 'http://purl.org/dc/terms/');
        foreach ($this->parse() as $key => $val) {
            /* Collection metadata is explicitly ignored */
            if (in_array($key, $this->filter))
                continue;

            if (is_array($val)) {
                foreach ($val as $v) {
                    $writer->startElement($key);
                    $writer->text($v);
                    $writer->endElement();
                }
            } else {
                $writer->startElement($key);
                $writer->text($val);
                $writer->endElement();
            }
        }

        /* End the atom entry */
        $writer->endElement();
        $writer->endDocument();
        return $writer->outputMemory(true);
    }

    function generateMets($fileMap = null) {
        $writer = new XMLWriter;
        $writer->openMemory();
        $writer->startDocument('1.0');

        /* METS Header */
        $writer->startElement('mets');
        $writer->writeAttribute('ID','sort-mets_mets');
        $writer->writeAttribute('OBJID','sword-mets');
        $writer->writeAttribute('LABEL','DSpace SWORD Item');
        $writer->writeAttribute('PROFILE','DSpace METS SIP Profile 1.0');

        /* Namespaces */
        $writer->writeAttribute('xmlns','http://www.loc.gov/METS');
        $writer->writeAttribute('xmlns:xlink','http://www.w3.org/1999/xlink');
        $writer->writeAttribute('xmlns:xsi','http://www.w3.org/2001/XMLSchema-instance');
        $writer->writeAttribute('xsi:schemaLocation','http://www.loc.gov/METS/ http://www.loc.gov/standards/mets/mets.xsd');

        /* Generate XML for each main section */
        $writer->startComment('dmdSec = Metadata');
        $writer->endComment();

        /* dmdSec [Descriptive Metadata] */
        $writer->startElement('dmdSec');
        foreach ($this->parse() as $key => $val) {
            if (in_array($key, $this->filter))
                continue;
            $writer->startElement($key);
            $writer->text($val);
            $writer->endElement();
        }
        $writer->endElement();

        /* fileSec [File Section] */
        if ($fileMap != null) {
            $writer->startComment('fileSec = Included file section');
            $writer->endComment();

            $writer->startElement('fileSec');
            foreach ($fileMap as $key => $val) {

            }
        }
        /* structMap [Structural Map] */
        /* required by all mets documents */


        /* End the atom entry */
        $writer->endElement();
        $writer->endDocument();
        return $writer->outputMemory(true);
    }
}