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
require 'vendor/autoload.php';

use Guzzle\Http\Client;
use Guzzle\Plugin\CurlAuth\CurlAuthPlugin;

class SwordService {

	/* The Guzzle client for making REST requests */
	var $client;

	function __construct($Base_URL, $User, $Pass) {
		$this->client = new Client($Base_URL);
		$authPlugin = new CurlAuthPlugin($User, $Pass); 
		$this->client->addSubscriber($authPlugin);
	}
	
	public function service_document() {
		$request = $this->client->get('/swordv2/servicedocument');
		return $request->send();
	}

	public function publish ($collection, $metadata) {
		$response = $this->client->get('/swordv2/servicedocument')->send();
		if ($response->getStatusCode() == 200) {
			/* Walk the service document for the requested collection */
			$xml = $response->xml();
			$this->register($xml);
			$xpath = "//sd:collection[atom:title/child::text()='$collection']";
			$collection = $xml->xpath ($xpath);
            $href = null;
			if ($collection != false) {
                foreach($collection[0]->attributes() as $a => $b) {
                    if ($a === 'href') {
                        $href = $b;
                    }
                }
                //$this->client->post($href, );

	    	} else {
	    		throw new Exception('Empty result!');
	    	}
		} else {
			throw new RequestException($response);
		}
		return $response;
	}

	private function register(&$xmlref) {
		$xmlref->registerXPathNamespace('sd','http://www.w3.org/2007/app');
		$xmlref->registerXPathNamespace('atom','http://www.w3.org/2005/Atom');
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

	/* Destroy this object, ensure that the Guzzle client is nulled out */
	public function __destroy() {
		$this->client = null;
	}
}