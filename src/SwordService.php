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
use Guzzle\Http\Message\PostFile;

class SwordService {
	function __construct($Base_URL, $User, $Pass) {
		$this->client = new Client($Base_URL);
		$authPlugin = new CurlAuthPlugin($User, $Pass); 
		$this->client->addSubscriber($authPlugin);
    }
	
	function service_document() {
		$request = $this->client->get('/swordv2/servicedocument');
		return $request->send();
	}

	function publish ($collection, $metadata) {
        /* First, find the file being referenced in the metadata, and
           if there, construct a Zip and retrieve the handle. (This
           function accepts just plain metadata publication.)
        */
        $zip = $this->saveZip($metadata["filename"]);

        /*  Construct AtomXMl document from metadata
            TODO: Flatten metadata and setup formatting for custom Mets ingestors.
        */
        $atom = $this->saveAtom($this->generateAtom($metadata));

        /* Get the href for the named collection */
        $href = $this->findHref($collection);

        /* If we have a publishing location, and metadata, POST */
        if ($href != null && $atom != null) {
            $request = $this->client->post($href);
            $request->addHeader('In-Progress', 'true');
            $atomPost = new PostFile('atom', $atom);
            $atomPost->setContentType('application/atom+xml');
            $request->addPostFile($atomPost);
            $zipPost = new PostFile('payload', $zip);
            $zipPost->setContentType('application/zip');
            $request->addPostFile($zipPost);

            try {
                $response = $request->send();
            } catch (Exception $exception) {
                unlink($zip);
                unlink($atom);
                throw $exception;
            }
        } else {
            throw new Exception('Unable to publish with null href or null atom document!');
        }

        /* Unlink the files from the file system */
        unlink($zip);
        unlink($atom);

        return $response;
	}

    private function saveZip($filename) {
        $zip = new ZipArchive();
        /* Requires a little more work for staging (e.g. multi-process < 1 second) */
        $zipfile = 'swordTemp_' . time() . '.zip';
        if ($zip->open($zipfile, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE)) {
            $zip->addFile($filename);
            $zip->close();
        }
        return $zipfile;
    }

	private function registerForSearch(&$xmlref) {
		$xmlref->registerXPathNamespace('sd','http://www.w3.org/2007/app');
		$xmlref->registerXPathNamespace('atom','http://www.w3.org/2005/Atom');
	}

    private function findHref($collection) {
        $href = null;
        $response = $this->client->get('/swordv2/servicedocument')->send();
        if ($response->getStatusCode() == 200) {
            /* Walk the service document for the requested collection */
            $xml = $response->xml();
            $this->registerForSearch($xml);
            $xpath = "//sd:collection[atom:title/child::text()='$collection']";
            $collection = $xml->xpath ($xpath);
            $href = null;
            if ($collection != false) {
                foreach($collection[0]->attributes() as $a => $b) {
                    if ($a === 'href') {
                        $href = $b;
                        break;
                    }
                }
            } else {
                throw new Exception('Empty result!');
            }
        } else {
            throw new RequestException($response);
        }
        return $href;
    }

    function saveAtom($atomxml) {
        $filename = 'atom' . time() . '.xml';
        $file = fopen($filename, 'w');
        fwrite($file, $atomxml);
        fclose($file);
        return $filename;
    }
	
	/* Generates an ATOM document from a given JSON dictionary */
	function generateAtom($document) {
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
        $writer->writeAttribute('xmlns:mets','http://www.loc.gov/METS/');

		foreach ($document as $key=>$val) {
			if ($key == 'author') {
				/* Author is a special case */
                $writer->startElement($key);
				$writer->startElement('name');
				$writer->text($val);
				$writer->endElement();
				$writer->endElement();
			} else if ($key == 'mets') {
                /* As is all METS data */
                echo('Mets data.');
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

	/* Destroy this object, ensure that the Guzzle client is nulled out */
	function __destroy() {
		$this->client = null;
    }
}