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
        $authPlugin = new CurlAuthPlugin($User, $Pass);
        $this->client->addSubscriber($authPlugin);
    }

    function service_document()
    {
        $request = $this->client->get('/swordv2/servicedocument');
        return $request->send();
    }

    function publish($metadata, $files, $types)
    {
        /* Get the href for the named collection */
        $atom = $this->generateAtom($metadata);
        $response = $this->postAtom($this->findCollectionHref($metadata['collection']), $atom);
        $href = $this->discoverEditMediaHref($response);
        $seiri = $this->discover_SEIRI_ref($response);
        $tc = 0;
        foreach ($files as $file) {
            $response = $this->postBinary($href, $file, $types[$tc++]);
        }
        return $this->postComplete($seiri);
    }

    private function postAtom($href, $atom)
    {
        $request = $this->client->post($href);
        $request->addHeaders(array(
            'Content-Type' => 'application/atom+xml;type=entry',
            'Content-Length' => strlen($atom),
            'In-Progress' => 'true',
            'X-Packaging' => 'http://purl.org/net/sword-types/METSDSpaceSIP',
        ));
        $request->setBody($atom);
        return $request->send();
    }

    private function postBinary($href, $binary, $type)
    {
        $request = $this->client->post($href);
        $eb = new \Guzzle\Http\EntityBody(fopen($binary, 'r'));
        $request->addHeaders(array(
            'Content-Type' => "$type",
            'Content-Disposition' => 'filename=' . $binary . '',
            'Content-Length' => $eb->getContentLength(),
            'In-Progress' => 'true',
            'X-Packaging' => 'http://purl.org/net/sword/package/Binary'
        ));
        $request->setBody($eb);
        return $request->send();
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

    private function discover_SEIRI_ref($response)
    {
        $xpath = "*[@rel='http://purl.org/net/sword/terms/add']";
        return $this->discover($xpath, $response);
    }

    private function discoverEditMediaHref($response)
    {
        $xpath = "*[@rel='edit-media']";
        return $this->discover($xpath, $response);
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