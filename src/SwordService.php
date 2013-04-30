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
require_once('vendor/swordapp/swordappv2-php-library/swordappclient.php');

class SwordService {

	var $URL, $User, $Pass;

	function __construct($URL, $User, $Pass) {
		$this->URL = $URL;
		$this->User = $User;
		$this->Pass = $Pass;
	}
	
	public function service_document() {
		$sword = new SWORDAPPClient();
        return $sword->servicedocument($this->URL . '/sword/servicedocument', $this->User, $this->Pass, null);
	}

	public function publish ($p_url, $metadata) {
		$sword = new SWORDAPPClient();
		$fileName = $metadata["filename"];
		return $sword->deposit($p_url, $this->User, $this->Pass, '', $fileName);
	}
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

	
}