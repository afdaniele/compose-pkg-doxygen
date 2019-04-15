<?php

class File{

  function __construct($argument){
    # code...
  }

}

function get_file_hierarchy(){
    $xml_location = \system\classes\Core::getSetting( "xml_location", "doxygen" );

    $dir_hierarchy = [];
    foreach( glob(sprintf("%s/dir_*.xml", $xml_location)) as $xml_file ){
    	preg_match_all("/^.*\/dir_([a-z0-9]{32}).xml$/", $xml_file, $matches);
    	// get dir ID
    	if( count($matches[1]) != 1 ) continue;
    	$dir_id = $matches[1][0];
    	array_push($dir_hierarchy, $dir_id);
    }

    $all_dirs = [];
    foreach( $xml_dirs as $xml_dir ){
    	// load XML as DOMDocument
        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = false;
        @$doc->loadHTML( file_get_contents($xml_dir) );

    	// create XPath object
    	$xpath = new DOMXpath($doc);

    	// get the ID of the directory
    	$dir_id = trim($xpath->query('//doxygen/compounddef/@id')->item(0).nodeValue);
    }

    // TODO: to be completed
}






?>
