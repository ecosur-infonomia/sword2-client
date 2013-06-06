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
        $writer->writeAttribute('xmlns:dc','http://dublincore.org/documents/dcmi-terms/');
        $writer->writeAttribute('xmlns:marc','http://dublincore.org/documents/marc-terms/');
        foreach ($this->parse() as $key => $val) {
            /* Filtered metadata is skipped */
            if (in_array($key, $this->filter))
                continue;

            /* Explode the key to find qualifier */
            $qualified = explode('.', $key);

            if (is_array($val)) {
                foreach ($val as $v) {
                    $this->writeElement($qualified, $writer, $key);
                    $writer->text($v);
                    $writer->endElement();
                }
            } else {
                $this->writeElement($qualified, $writer, $key);
                $writer->text($val);
                $writer->endElement();
            }
        }

        /* End the atom entry */
        $writer->endElement();
        $writer->endDocument();
        return $writer->outputMemory(true);
    }

    /**
     * @param $qualified
     * @param $writer
     * @param $key
     */
    private function writeElement($qualified, $writer, $key)
    {
        /* Special case for marc */
        if ($qualified[0] == 'marc') {
            $writer->startElement($qualified[0] . ':dcterm');
            $writer->writeAttribute('element',$qualified[1]);
            if (count ($qualified) > 2) {
                $writer->writeAttribute('qualifier',$qualified[2]);
            }
        } else {
            if (count($qualified) > 1) {
                $writer->startElement($qualified[0]);
                $writer->writeAttribute('qualifier', $qualified[1]);
            } else {
                $writer->startElement($key);
            }
        }
    }
}