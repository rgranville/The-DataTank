<?php
  /**
   * The module handler will look for GET and POST requests on a certain module. It will ask the factories to return the right Resource instance.
   * If it checked all required parameters, checked the format, it will perform the call and get a result. This result is printer by a printer returned from the PrinterFactory
   *
   * @package The-Datatank/handlers
   * @copyright (C) 2011 by iRail vzw/asbl
   * @license AGPLv3
   * @author Pieter Colpaert
   * @author Jan Vansteenlandt
   */
include_once('printer/PrinterFactory.php');
include_once('handlers/RequestLogger.class.php');
include_once('factories/FilterFactory.class.php');
include_once('resources/GenericResource.class.php');

class ModuleHandler {

    private $printerfactory;
    
    function GET($matches) {
        
	//always required: a module and a resource. This will always be given since the regex should be matched.
	$module = $matches['module'];
	$resourcename = $matches['resource'];

	//This will create an instance of a factory depending on which format is set
	$this->printerfactory = PrinterFactory::getInstance();
	
	//This will create an instance of AResource
	$factory= AllResourceFactory::getInstance();
	$resource = $factory->getResource($module,$resourcename);

	$RESTparameters = array();
	if(isset($matches['RESTparameters'])){
	    $RESTparameters = explode("/",$matches['RESTparameters']);
	    array_pop($RESTparameters); // remove the last element because that just contains the GET parameters
	}
        
        $requiredparams = array();

        foreach($factory->getResourceRequiredParameters($module,$resourcename) as $parameter){
            //set the parameter of the method
            if(!isset($RESTparameters[0])){
                throw new ParameterTDTException($parameter);
            }
            $resource->setParameter($parameter, $RESTparameters[0]);
            $requiredparams[$parameter]=$RESTparameters[0];
	    
            //removes the first element and reindex the array
            array_shift($RESTparameters);
        }
        //what remains in the $resources array are specification for a RESTful way of identifying objectparts
        //for instance: http://api.../TDTInfo/Modules/module/1/ would make someone only select the second module

        //also give the non REST parameters to the resource class
        $resource->processParameters();
    
	
        // check if the given format is allowed by the method
        $printmethod = "";
        foreach($factory->getAllowedPrintMethods($module,$resourcename) as $printername){
            if(strtolower($this->printerfactory->getFormat()) == strtolower($printername)){
                $printmethod = $printername;
                break;//I have sinned again
            }
        }

        //if the printmethod is not allowed, just throw an exception
        if($printmethod == ""){
            throw new FormatNotAllowedTDTException($this->printerfactory->getFormat(),$resource->getAllowedPrintMethods());
        }

        //Let's do the call!
        $result = $resource->call();

        // for logging purposes
        $subresources = array();
        $filterfactory = FilterFactory::getInstance();
        // apply RESTFilter
        if(sizeof($RESTparameters)>0){
	    
            $RESTFilter = $filterfactory->getFilter("RESTFilter",$RESTparameters);
            $resultset = $RESTFilter->filter($result);
            $subresources = $resultset->subresources;
            $result = $resultset->result;
        }
	
        //Apply Lookup filter if asked, according to the Open Search specifications
	
        if(isset($_GET["filterBy"]) && isset($_GET["filterValue"])){
            if(!is_array($result)){
                throw new FilterTDTException("The object provided is not a collection."); 
            }else{
                $filterparameters = array();
                $filterparameters["filterBy"] = $_GET["filterBy"];
                $filterparameters["filterValue"] = $_GET["filterValue"];
                if(isset($_GET["filterOp"])){
                    $filterparameters["filterOp"] = $_GET["filterOp"];
                }
		
                $searchFilter = $filterfactory->getFilter("SearchFilter",$filterparameters);
                $result = $searchFilter->filter($result);
            }	    
        }
	
        if(!is_object($result)){
            $o = new stdClass();
            $RESTresource = "";
            if(sizeof($RESTparameters)>0){
                $RESTresource = $RESTparameters[sizeof($RESTparameters)-1];
            }else{
                $RESTresource = $resourcename;
            }
            
            $o->$RESTresource = $result;
            $result = $o;
        }
	
        // Log our succesful request
        RequestLogger::logRequest($matches,$requiredparams,$subresources);
	
        $printer = $this->printerfactory->getPrinter(strtolower($resourcename), $result);
        $printer->printAll();
        //this is it!
    }

    function PUT($matches){
        if($_SERVER['PHP_AUTH_USER'] == Config::$API_USER && $_SERVER['PHP_AUTH_PW'] == Config::$API_PASSWD){
            parse_str(file_get_contents("php://input"),$put_vars);
            // var_dump($put_vars);
            $resource = $matches["resource"];
            $module = $matches["module"];
            /*
             * Check if a correct resource_type has been set, 
             * after that apply the correct database interfacing to add the resource,
             * and provide the correct feedback towards the user (errorhandling etc.)
             * Note: There are alot of Exceptions that can be thrown here, this in order to provide
             * the best feedback towards the user. If this is used as the back-end of a form
             * that logic will need to know what field was incorrect, or what else went wrong (i.e. resource already exists)
             */
            if(isset($put_vars["resource_type"])){
                $resource_type = $put_vars["resource_type"];
                if($resource_type == "generic_resource"){
                    try{
                        $generic_type = $put_vars["generic_type"];  
                        R::setup(Config::$DB, Config::$DB_USER, Config::$DB_PASSWORD);
                        // check if the module exists, if not create it. Either way, retrieve
                        // the id from the module entry
                        $module_id = $this->evaluateModule($module);
                        $resource_id = $this->evaluateGenericResource($module_id,$module,$put_vars);
                        if($generic_type == "DB"){
                            $this->evaluateDBResource($resource_id,$put_vars);
                        }elseif($generic_type == "CSV"){
                            $this->evaluateCSVResource($resource_id,$put_vars);
                        }else{
                            throw new Exception("resource type: ".$resource_type. " is not supported.");
                        }
                    }catch(Exception $ex){
                        throw new ResourceAdditionTDTException("Something went wrong while adding the resource: "
                                                               . $ex->getMessage());
                    }
                }elseif($resource_type == "remote_resource"){
                    try{
                        R::setup(Config::$DB, Config::$DB_USER, Config::$DB_PASSWORD);
                        $module_id = evaluateModule($module);
                        $this->evaluateRemoteResource($module_id,$resource,$put_vars);
                    }catch(Exception $ex){
                        throw new ResourceAdditionTDTException("Something went wrong while adding the resource: "
                                                               . $ex->getErrorMessage());
                    }
                }else{
                    throw new ResourceAdditionTDTException("The addition type given, "
                                                           .$put_vars["resource_type"] . ", is not supported.");
                }
            }else{
                throw new ResourceAdditionTDTException("No addition type was given. Addition types are: generic_resource and remote_resource");
            }
        }else{
            throw new ValidationTDTException("You're not allowed to perform this action.");
        }
    }

    private function evaluateModule($module){
        $result = R::getAll(
            "select id from module where module_name=:module_name",
            array(":module_name"=>$module)
        );
        if(sizeof($result)==0){
            $newmodule = R::dispense("module");
            $newmodule->module_name = $module;
            $id = R::store($newmodule);
            return $id;
        }else{
            return $result[0]["id"];
        }
    }

    private function evaluateGenericResource($module_id,$resource,$put_vars){
        $genres = R::dispense("generic_resource");
        $genres->module_id = $module_id;
        $genres->resource_name = $resource;
        $genres->type = $put_vars["generic_type"];
        $genres->documentation = $put_vars["documentation"];
        $genres->print_methods =  $put_vars["printmethods"];;
        return R::store($genres);
    }

    private function evaluateDBResource($resource_id,$put_vars){
        $dbresource = R::dispense("generic_resource_db");
        $dbresource->resource_id = $resource_id;
        $dbresource->dbtype = $put_vars["dbtype"];
        $dbresource->dbname = $put_vars["dbname"];
        $dbresource->dbtable = $put_vars["dbtable"];
        $dbresource->host = $put_vars["host"];
        $dbresource->port = $put_vars["port"]; // is this obliged ? default port?
        $dbresource->user = $put_vars["user"];
        $dbresource->password = $put_vars["password"];
        $dbresource->columns = $put_vars["columns"];    
        R::store($dbresource);
    }

    private function evaluateCSVResource($resource_id,$put_vars){
        $csvresource = R::dispense("generic_resource_csv");
        $csvresource->resource_id = $resource_id;
        $csvresource->uri = $put_vars["uri"];
        $csvresource->columns = $put_vars["columns"];
        R::store($csvresource);
    }

    private function evaluateRemoteResource($module_id,$resource,$put_vars){
        $remres = R::dispense("remote_resource");
        $remres->module_id = $module_id;
        $remres->resource_name = $resource;
        $remres->module_name = $put_vars["module_name"];
        $remres->base_url = $put_vars["url"]; // make sure this url ends with a /
        R::store($remres);
    }  
}

?>