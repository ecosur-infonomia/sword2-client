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

class SwordService
{
    function __construct($Base_URL, $User, $Pass)
    {
        $this->client = new Client($Base_URL);
        $this->obo = $User; /* required for deletes */
        $authPlugin = new CurlAuthPlugin($User, $Pass);
        $this->client->addSubscriber($authPlugin);
    }

    function service_document()
    {
        $request = $this->client->get('/swordv2/servicedocument');
        return $request->send();
    }

    function publishWithAtom($collection, $metadata, $fMap)
    {
        /* Get the href for the named collection */
        $atom = $this->generateAtom($metadata);
        $response = $this->postXmlMetadata($this->findCollectionHref($collection), $atom);
        $href = $this->discover_EMIRI_ref($response);
        $seiri = $this->discover_SEIRI_ref($response);
        $response = $this->postBinaries($href, $fMap);
        if ($response->getStatusCode() == 201) {
            return $this->postComplete($seiri);
        }
    }

    /* Publishes the mets zip file "$zip".
    */
    function publishWithMets($collection, $zip) {
        $href = $this->findCollectionHref($collection);
        $request = $this->client->post($href);
        $eb = new \Guzzle\Http\EntityBody(fopen($zip, 'r'));
        $request->addHeaders(array(
            'Content-Type' => "application/zip",
            'Content-Disposition' => 'filename=' . $zip . '',
            'Content-Length' => $eb->getContentLength(),
            'In-Progress' => 'false',
            'Packaging' => 'http://purl.org/net/sword/package/METSDSpaceSIP'
        ));
        $request->setBody($eb);
        return $request->send();
    }

    function delete($iri) {
        $request = $this->client->delete($iri);
        $request->addHeader('On-Behalf-Of', $this->obo);
        return $request->send();
    }

    function discover_SEIRI_ref($response)
    {
        $xpath = "*[@rel='http://purl.org/net/sword/terms/add']";
        return $this->discover($xpath, $response);
    }

    function discover_EMIRI_ref($response)
    {
        $xpath = "*[@rel='edit-media']";
        return $this->discover($xpath, $response);
    }

    function discover_EIRI_ref($response) {
        $xpath = "*[@rel='edit']";
        return $this->discover($xpath, $response);
    }

    /* Posts XML metadata to the server with a default type of atom+xml */
    private function postXmlMetadata($href, $xml, $type = 'application/atom+xml;type=entry')
    {
        $request = $this->client->post($href);
        $request->addHeaders(array(
            'Content-Type' => $type,
            'Content-Length' => strlen($xml),
            'In-Progress' => 'true',
            'Packaging' => 'http://purl.org/net/sword-types/METSDSpaceSIP',
        ));
        $request->setBody($xml);
        return $request->send();
    }

    private function postBinaries($href, $bMap)
    {
        $lastResponse = null;
        foreach ($bMap as $entry) {
            $packaging = 'http://purl.org/net/sword/package/Binary';
            /* A few error checks */
            if (array_key_exists('package', $entry)) {
                $packaging = $entry['package'];
            }
            if (!array_key_exists('file', $entry)) {
                throw new ErrorException("Mapped file is required!");
            }
            if (!array_key_exists('type', $entry)) {
                throw new Exception("Mapped file type is required!");
            }
            $request = $this->client->post($href);
            $eb = new \Guzzle\Http\EntityBody(fopen($entry['file'], 'r'));
            $request->addHeaders(array(
                'Content-Type' => "$entry[type]",
                'Content-Disposition' => 'filename=' . $entry['file'] . '',
                'Content-Length' => $eb->getContentLength(),
                'In-Progress' => 'true',
                'Packaging' => $packaging
            ));
            $request->setBody($eb);
            $lastResponse = $request->send();
        }
        return $lastResponse;
    }

    private function postComplete($href)
    {
        $request = $this->client->post($href);
        $request->addHeaders(array(
            'In-Progress' => 'false'
        ));
        return $request->send();
    }

    private function findCollectionHref($collection)
    {
        $href = null;
        $response = $this->service_document();
        if ($response->getStatusCode() == 200) {
            /* Walk the service document for the requested collection */
            $xml = $response->xml();
            $this->registerNamespaceForXpath($xml);
            $xpath = "//sd:collection[atom:title/child::text()='$collection']";
            $collection = $xml->xpath($xpath);
            $href = null;
            if ($collection != false) {
                foreach ($collection[0]->attributes() as $a => $b) {
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

    private function discover($xpath, $response)
    {
        $href = null;
        $receipt = $response->xml();
        $this->registerNamespaceForXpath($receipt);
        $edit = $receipt->xpath($xpath);
        foreach ($edit[0]->attributes() as $a => $b) {
            if ($a === 'href') {
                $href = $b;
                break;
            }
        }
        return $href;
    }

    private function registerNamespaceForXpath(&$xmlref)
    {
        $xmlref->registerXPathNamespace('sd', 'http://www.w3.org/2007/app');
        $xmlref->registerXPathNamespace('atom', 'http://www.w3.org/2005/Atom');

    }

    /* Generates an ATOM document from a given JSON dictionary */
    function generateAtom($document)
    {
        $writer = new XMLWriter;
        $writer->openMemory();
        $writer->startDocument('1.0');

        /* Atom Entry */
        $writer->startElement('atom:entry');
        $writer->writeAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
        $writer->writeAttribute('xmlns:dc', 'http://purl.org/dc/terms');
        $writer->writeAttribute('xmlns:mets', 'http://www.loc.gov/METS/');

        foreach ($document as $key => $val) {
            if ($key == 'atom:author') {
                /* Author is a special case */
                $writer->startElement('atom:author');
                $writer->startElement('atom:name');
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
    function __destroy()
    {
        $this->client = null;
    }
}