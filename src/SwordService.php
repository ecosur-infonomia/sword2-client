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

	function __construct($Base_URL, $User, $Pass) {
		$this->client = new Client($Base_URL);
		$authPlugin = new CurlAuthPlugin($User, $Pass); 
		$this->client->addSubscriber($authPlugin);
    }
	
	function service_document() {
		$request = $this->client->get('/swordv2/servicedocument');
		return $request->send();
	}

	function publish ($metadata, $files) {
        /* Get the href for the named collection */
        $href = $this->findHref($metadata['collection']);

        /*  Construct AtomXMl document from metadata
            TODO: Flatten metadata and setup formatting for custom Mets ingestors.
        */
        $atom = $this->generateAtom($metadata);
        $zip = $this->constructZip($files);

        $response = null;
        try {
            $response = $this->postZip($href, $zip);
            $href = $this->findEditHref($response);
            $response = $this->putAtom($href, $atom);
        } catch (Exception $e) {
            if (isset($zip)) {
                unlink($zip);
            }
            throw $e;
        }

        /* Try to always unlink (delete) zip */
        if (isset($zip)) {
            unlink($zip);
        }

        /* Return HTTP response */
        return $response;
	}

    private function putAtom($href, $atom) {
        $request = $this->client->put($href);
        $request->addHeaders(array(
            'Content-Type'=>'application/atom+xml;type=entry',
            'Content-Length'=>strlen($atom),
            'In-Progress'=>'false',
            'X-Packaging'=>'http://purl.org/net/sword-types/METSDSpaceSIP',
        ));
        $request->setBody($atom);
        return $request->send();
    }

    private function postZip($href, $binary) {
        $request = $this->client->post($href);
        $eb = new \Guzzle\Http\EntityBody(fopen($binary,'r'));
        $request->addHeaders(array(
            'Content-Type'=>'application/zip',
            'Content-Disposition'=>'filename=' . $binary . '',
            'Content-Length'=>$eb->getContentLength(),
            'In-Progress'=>'true',
            'X-Packaging'=>'http://purl.org/net/sword-types/METSDSpaceSIP',
        ));
        $request->setBody($eb);
        return $request->send();
    }

    private function constructZip($files) {
        $zip = new ZipArchive();
        /* Requires a little more work for staging (e.g. multi-process < 1 second) */
        $zipfile = 'swordTemp_' . time() . '.zip';
        if ($zip->open($zipfile, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE)) {
            foreach ($files as $file) {
                $zip->addFile($file);
            }
            $zip->close();
        }
        return $zipfile;
    }

    private function findHref($collection) {
        $href = null;
        $response = $this->service_document();
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

    private function registerForSearch(&$xmlref) {
        $xmlref->registerXPathNamespace('sd','http://www.w3.org/2007/app');
        $xmlref->registerXPathNamespace('atom','http://www.w3.org/2005/Atom');

    }

    private function findEditHref($response)
    {
        $receipt = $response->xml();
        $this->registerForSearch($receipt);
        $edit_href = null;
        $xpath = "*[@rel='edit']";
        $edit = $receipt->xpath($xpath);
        foreach ($edit[0]->attributes() as $a => $b) {
            if ($a === 'href') {
                $edit_href = $b;
                break;
            }
        }
        return $edit_href;
    }

	/* Generates an ATOM document from a given JSON dictionary */
	function generateAtom($document) {
		$writer = new XMLWriter;
		$writer->openMemory();
		$writer->startDocument('1.0');

		/* Atom Entry */
		$writer->startElement('atom:entry');
		$writer->writeAttribute('xmlns:atom','http://www.w3.org/2005/Atom');
		$writer->writeAttribute('xmlns:dcterms','http://purl.org/dc/terms');
        $writer->writeAttribute('xmlns:mets','http://www.loc.gov/METS/');

		foreach ($document as $key=>$val) {
			if ($key == 'author') {
				/* Author is a special case */
                $writer->startElement('author');
				$writer->startElement('name');
				$writer->text($val);
				$writer->endElement();
				$writer->endElement();
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