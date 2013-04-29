<?php
/**  Copyright 2013 ECOSUR and Andrew Waterman **/

/**
 * SwordService.php
 *
 * Entrypoint for sword publishing service. This class expects to
 * be given a handle to the binary file to be published (unzipped,
 * this utility will zip the binary to publication with SWORD) and
 * a handle to the XML metatdata to be published along with the 
 * bitstream.
 *
 * Author: "Andrew G. Waterman" <awaterma@ecosur.mx>
**/
require 'config.php';

class SwordService {

	/* Generates an ATOM document from a given JSON dictionary */
	public function generate_atom($document) {
		/* Explicitly unset the filename key from the document array */
		/* Magic Key */
		unset($document["filename"]);
		$writer = new XMLWriter;
		$writer->openMemory();
		$writer->startDocument('1.0');
		
		/* Atom Entry */
		$writer->startElement('entry');
		$writer->writeAttribute('xmlns:atom','http://www.w3.org/2005/Atom');
		$writer->writeAttribute('xmlns:dcterms','http://purl.org/dc/terms');

		foreach ($document as $key=>$val) {
			/* Author is a special case */
			if ($key == 'author') {
				$writer->startElement($key);
				$writer->startElement('name');
				$writer->text($val);
				$writer->endElement();
				$writer->endElement();
			} 
			else {
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

	public function publish ($metadata) {
		$file = $metadata["filename"];
		/* Convert the json metadata block into an associative array
		   for processing */
		$atom = generate_atom($document);	
	}
}