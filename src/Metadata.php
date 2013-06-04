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
}