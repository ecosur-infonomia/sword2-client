<?php
/**  Copyright 2013 ECOSUR and Andrew Waterman **/
use Guzzle\Http\Client;
use Guzzle\Plugin\CurlAuth\CurlAuthPlugin;

/**
 * SwordService.php
 *
 * Entrypoint for sword publishing service. Metadata
 * processing is handled externally, in the Metadata
 * class.
 *
 * Author: "Andrew G. Waterman" <awaterma@ecosur.mx>
 **/
require 'vendor/autoload.php';
require 'Metadata.php';

class SwordService
{
    function __construct($Base_URL, $User, $Pass)
    {
        $this->client = new Client($Base_URL);
        $this->obo = $User; /* On-behalf-of is this $User */
        $authPlugin = new CurlAuthPlugin($User, $Pass);
        $this->client->addSubscriber($authPlugin);
    }

    function publish($metadata, $fMap = null)
    {
        $meta = new Metadata($metadata);
        $parsed = $meta->parse();
        $collections = $parsed['collection'];
        if (is_array($collections)) {
            /* First collection named will become owning collection */
            $primary = $collections[0];
            unset($collections[$primary]);
            $resp = $this->publishWithAtom($primary, $meta, $fMap);
            /* Affiliate request with named collections */
            $se_iri = $this->discover_SEIRI_ref($resp);
            return $this->affiliate($se_iri, $collections);
        } else {
            return $this->publishWithAtom($collections, $meta, $fMap);
        }
    }

    private function publishWithAtom($collection, $metadata, $fMap = null)
    {
        /* Get the href for the named collection */
        $atom = $metadata->generateAtom();
        echo("\r\n" . $atom . "\r\n");
        $response = $this->postXmlMetadata($this->discover_COLIRI_ref($collection), $atom);
        $seiri = $this->discover_SEIRI_ref($response);
        if ($fMap != null) {
            $emiri = $this->discover_EMIRI_ref($response);
            $response = $this->postBinaries($emiri, $fMap);
        }

        if ($response->getStatusCode() == 200 || $response->getStatusCode() == 201) {
            return $this->postComplete($seiri);
        } else {
            return $response;
        }
    }

    /*
     * Publishes all resources in the zip, "$zip" into the collection
     * "$collection" as a METS expressed package.
     */

    private function postXmlMetadata($href, $xml, $progress = 'true',
                                     $packaging = 'http://purl.org/net/sword-types/METSDSpaceSIP')
    {
        $request = $this->client->post($href);
        $request->addHeaders(array(
            'Content-Type' => 'application/atom+xml;type=entry',
            'Content-Length' => strlen($xml),
            'In-Progress' => $progress,
            'Packaging' => $packaging,
            'On-Behalf-Of' => $this->obo
        ));
        $request->setBody($xml);
        return $request->send();
    }

    /*
     * Takes the SE-IRI of the Item to be updated, and an array
     * of named collections for the item to be affiliated with.
     */

    function discover_COLIRI_ref($collection)
    {
        $response = $this->service_document();
        $xpath = "//sd:collection[atom:title/child::text()='$collection']";
        return $this->discoverHref($xpath, $response);
    }

    function service_document()
    {
        $request = $this->client->get('/swordv2/servicedocument');
        return $request->send();
    }

    private function discoverHref($xpath, $response)
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

    function discover_SEIRI_ref($response)
    {
        $xpath = "*[@rel='http://purl.org/net/sword/terms/add']";
        return $this->discoverHref($xpath, $response);
    }

    function discover_EMIRI_ref($response)
    {
        $xpath = "*[@rel='edit-media']";
        return $this->discoverHref($xpath, $response);
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

    function affiliate($SE_IRI, $collections)
    {
        /* Constructs an Atom+XML for posting to the se-iri using
           the Affilate.xsd schema for affiliating schemas to
           collections during a meta-data update to the server.
        */
        $writer = new XMLWriter;
        $writer->openMemory();
        $writer->startDocument('1.0');

        /* Atom Entry */
        $writer->startElement('atom:entry');
        $writer->writeAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
        $writer->writeAttribute('xmlns:dc', 'http://purl.org/dc/terms');
        $writer->writeAttribute('xmlns:mx', 'http://www.ecosur.mx/swordv2');
        $writer->startElement('mx:affiliate');
        foreach ($collections as $collection) {
            $writer->startElement('mx:collection');
            $writer->writeAttribute('name', $collection);
            $writer->endElement();
        }
        $writer->endElement(); //mx:affiliate
        $writer->endElement(); //atom:entry
        $writer->endDocument();
        $xml = $writer->outputMemory(true);
        return $this->postXmlMetadata($SE_IRI, $xml, 'false', 'http://www.ecosur.mx/swordv2');
    }

    function publishZipWithMets($collection, $zip)
    {
        $href = $this->discover_COLIRI_ref($collection);
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

    function delete($iri)
    {
        $request = $this->client->delete($iri);
        $request->addHeader('On-Behalf-Of', $this->obo);
        return $request->send();
    }

    function discover_EIRI_ref($response)
    {
        $xpath = "*[@rel='edit']";
        return $this->discoverHref($xpath, $response);
    }

    /* Destroy this object, ensure that the Guzzle client is nulled out */

    function __destroy()
    {
        $this->client = null;
    }
}