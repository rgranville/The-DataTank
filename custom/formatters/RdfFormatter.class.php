<?php
/**
 * This file contains the RDF/XML formatter.
 * 
 * Includes RDF Api for PHP <http://www4.wiwiss.fu-berlin.de/bizer/rdfapi/>
 * Licensed under LGPL <http://www.gnu.org/licenses/lgpl.html>
 *
 * @package The-Datatank/formatters
 * @copyright (C) 2011 by iRail vzw/asbl
 * @license AGPLv3
 * @author Miel Vander Sande
 */
class RdfFormatter extends AFormatter {

    public function __construct($rootname, $objectToPrint) {
        parent::__construct($rootname, $objectToPrint);
    }

    public function printBody() {
        //Unwrap the object       
        foreach ($this->objectToPrint as $class => $prop){
            if (is_a($prop,"MemModel")){
                $this->objectToPrint = $prop;
                break;
            }
        }
        //When the objectToPrint has a MemModel, it is already an RDF model and is ready for serialisation.
        //Else it's retrieved data of which we need to build an rdf output
        if (!is_a($this->objectToPrint,"MemModel")) {
            $outputter = new RDFOutput();
            $this->objectToPrint = $outputter->buildRdfOutput($this->objectToPrint);
        }

        // Import Package Syntax
        include_once(RDFAPI_INCLUDE_DIR . PACKAGE_SYNTAX_RDF);

        $ser = new RDFSerializer();

        $rdf = $ser->serialize($this->objectToPrint);

        echo $rdf;
    }

    public function printHeader() {
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/rdf+xml; charset=UTF-8");
        header("Content-Type: text/xml; charset=UTF-8");
 
    }

    public static function getDocumentation(){
        return "Prints the RDF/XML notation with semantic annotations";
    }

}

?>
