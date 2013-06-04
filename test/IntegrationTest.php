<?php 

/**
 * This test uses the Sword test server running at the dspace demo server.
 * In order to be succesful, the server must be reachable from the client. 
 */
require_once('src/SwordService.php');
require_once('TestConfig.php');

class IntegrationTest extends PHPUnit_Framework_TestCase {

    function testConnectivity() {
        $sword = new SwordService(TestURL, TestUser, TestPass);
        $resp = $sword->service_document();
        $this->assertEquals(200,$resp->getStatusCode());
    }

    function testFileDeposit() {
        $atomMetaJSON = '{' .
            '"collection" : "Collection of Sample Items", '.
            '"atom:author" : "Stuart Lewis" ,'.
            '"atom:title" : "If sword is the Answer" ,'.
            '"atom:summary" : "The purpose of this paper is to describe the repository deposit protocol, Simple Web-service Offering Repository Deposit (SWORD), its development iteration, and some of its potential use cases. In addition, seven case studies of institutional use of SWORD are provided." ,'.
            '"atom:updated" : "May 5, 2012" ,'.
            '"dcterms:abstract" : "Simple abstract" ,'.
            '"dcterms:available" : "2013" ,'.
            '"dcterms:accessRights" : "Open" ,'.
            '"dcterms:description" : "A PDF on Sword" ,'.
            '"dcterms:contributor" : "Stuart Lewis" ,'.
            '"dcterms:creator" : "Stuart Lewis" ,'.
            '"dcterms:format" : "PDF" '.
            '}';
        $sword = new SwordService(TestURL, TestUser, TestPass);
        $map = array(
            array('file'=>'test/resources/Sword.pdf', 'type'=>'application/pdf'),
            array('file'=>'test/resources/Example.zip','type'=>'application/zip',
                'package'=>'http://purl.org/net/sword/package/SimpleZip')
        );
        $resp = $sword->publish($atomMetaJSON, $map);
        $this->assertEquals(200,$resp->getStatusCode(), 'Publication Not Accepted! ' . $resp->getBody(true));
    }

    function testMetsDeposit() {
        $sword = new SwordService(TestURL, TestUser, TestPass);
        $resp = $sword->publishZipWithMets('Collection of Sample Items', 'test/resources/example.zip');
        $this->assertEquals(201,$resp->getStatusCode(), 'Publication Not Accepted! ' . $resp->getBody(true));
    }

    function testInfonomiaMetadata() {
        $metadata = '{'.
            '"collection" : ["Tesis", '.
                '"Yuself Roberto Cala De La Hera", '.
                '"Martha García Ortega", '.
                '"Juan Carlos Pérez Jiménez"], '.
            '"dcterms:contributor" : [ "Yuself Roberto Cala De La Hera", '.
                '"Alberto De Jesús-navarrete", '.
                '"Martha García Ortega", '.
                '"Pedro M. Alcolado Menéndez", '.
                '"Juan Carlos Pérez Jiménez"], '.
            '"dcterms:date.issued" : "2012" ,'.
            '"dcterms:abstract" : "Muchos pequeños agricultores que viven en la Reserva de la Biosfera El Triunfo (REBITRI), han eliminado una parte de los bosques para cultivar café, contraponiendo sus objetivos de subsistencia con los objetivos de conservación de la reserva. Desde el año 2004, varios ejidos de la reserva han recibido pagos por servicios ambientales para conservar los bosques que proporcionan dichos servicios. Evitar la deforestación tiene un costo potencial para las familias, que se conoce como costo de oportunidad y hace referencia a los ingresos que los productores dejan de ganar cuando elijen una alternativa de uso del suelo que produce menos beneficios. Conocer el costo de oportunidad permite calcular cuánto debe compensarse a las familias en programas que buscan conservar los bosques. Los objetivos de este trabajo fueron 1) determinar el costo de oportunidad del cultivo de café en los ejidos Siete de Octubre y Piedra Blanca ubicados en la REBITRI, 2 ) conocer si el programa de Pagos por Servicios Ambientales-Hidrológicos (PSA-H) de la CONAFOR compensa este costo y 3) Conocer, para tener una análisis más completo, el beneficio neto del uso del bosque. Se hizo una contabilidad detallada de los costos y beneficios de la producción de café y del uso del bosque para 23 familias. En el año 2010, cuando el precio del café fue relativamente bajo, el beneficio neto del café fue $3,434/ha. El PSA-H cubriría el costo de oportunidad del 39% de los productores. Realizar una simulación con un precio dos veces más alto disminuyó el porcentaje de productores para los cuales el PSA-H sí cubre el costo de oportunidad, se redujo de 39% a 9%." ,'.
            '"dcterms:available" : "2013" ,'.
            '"dcterms:title" : "Análisis económico de la producción de café y uso del bosque en la Reserva de la Biosfera El Triunfo, Chiapas" ,'.
            '"dcterms:subject" : ["café", '.
                '"Productividad agrícola", '.
                '"Análisis económico", '.
                '"conservación de bosques",'.
                '"Pago por servicios ecosistémicos",'.
                '"Pago por servicios ambientales hídricos"], '.
            '"dcterms:subject.classification" : "TE/338.173730972" ,'.
            '"dcterms:subject.other" : ["Siete de Octubre, Ángel Albino Corzo (Chiapas, México)",'.
                '"Piedra Blanca, La Concordia (Chiapas, México)"], '.
            '"dcterms:accessRights" : "Derechos reservados a ECOSUR" ,'.
            '"dcterms:format" : "27 cm." ,'.
            '"dcterms:format.extent" : "126 h." ,'.
            '"dcterms:format.medium" : "fot., il., mapas, retrs." ,'.
            '"dcterms:language.iso" : "es" ,'.
            '"dcterms:publisher" : "El Colegio de la Frontera Sur" ,'.
            '"dcterms:type" : "Tesis de maestría" ,'.
            '"dcterms:rae.idsibe" : "000051465" ,'.
            '"marc.260.a" : "San Cristóbal de Las Casas, Chiapas, México" ,'.
            '"marc.856.u" : "http://200.23.34.72:8991/F?func=service&doc_library=CFS01&doc_number=000051465&line_number=0001&func_code=DB_RECORDS&service_type=MEDIA" ,'.
            '"dc.rae.urlportada" : "http://200.23.34.14/sibe/portadas/51465.png" '.
        '}';

        $sword = new SwordService(TestURL, TestUser, TestPass);
        $resp = $sword->publish($metadata);
        $this->assertEquals(200,$resp->getStatusCode(), 'Publication Not Accepted! ' . $resp->getBody(true));
    }
}