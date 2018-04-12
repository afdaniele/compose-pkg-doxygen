<?php

$files_to_process = [
	"classsystem_1_1classes_1_1_core.xml",
	"classsystem_1_1classes_1_1_configuration.xml",
	"classsystem_1_1classes_1_1_editable_configuration.xml"
];

$xml_location = \system\classes\Core::getSetting( "xml_location", "doxygen" );
$md_location = \system\classes\Core::getSetting( "md_location", "doxygen" );


foreach( $files_to_process as $xml_file ){
	$md_file = str_replace(".xml", ".md", $xml_file);
	$md_content = "";

	$xml_file_path = sprintf("%s/%s", $xml_location, $xml_file);
	$md_file_path = sprintf("%s/%s", $md_location, $md_file);

	// load XML as DOMDocument
	$doc = new DOMDocument();
	$doc->preserveWhiteSpace = false;
	@$doc->loadHTML( file_get_contents($xml_file_path) );

	// create XPath object
	$xpath = new DOMXpath($doc);


	$compounds = $xpath->query("//doxygen/compounddef");


	foreach( $compounds as $compound ){

		$compound_id = $compound->getAttribute('id');
		$compound_name = $xpath->query( "compoundname", $compound )->item(0)->nodeValue;
		$compound_description = $xpath->query( "detaileddescription", $compound )->item(0)->nodeValue;

		$compound_prefix_len = strlen($compound_id)+2;

		// Add H1 title
		$md_content .= sprintf("\n# Code reference: **%s**\n", $compound_name);

		// Create description section
		$md_content .= sprintf("\n## Description\n%s\n\n", $compound_description);

		// // Create table of contents
		// $md_content .= "\n## Table of Contents\n[toc]\n\n";

		// Get all the public static functions
		$functions = $xpath->query(
			"sectiondef[@kind='public-static-func']/memberdef[@kind='function'][@prot='public']",
			$compound
		);
		$md_content .= "\n## Static Public Member Functions\n";
		$md_content .= "\n<table class=\"table table-striped table-condensed\">\n";
		foreach( $functions as $function ){
			$md_content .= sprintf(
				"\n<tr><td> %s </td><td> <a href=\"#%s\"><bold>%s</bold></a> %s </td></tr>",
				$xpath->query("type", $function)->item(0)->nodeValue,
				substr($function->getAttribute('id'), $compound_prefix_len),
				$xpath->query("name", $function)->item(0)->nodeValue,
				$xpath->query("argsstring", $function)->item(0)->nodeValue
			);
		}
		$md_content .= "\n</table>";

		// Create independent sections for each function
		$md_content .= "\n## Member Function Documentation\n";
		foreach( $functions as $function ){
			$md_content .= sprintf(
				'<div class="panel panel-primary reference-code-documentation-panel">
					<div class="panel-heading">
						<h3 class="panel-title" id="%s">%s <bold>%s</bold> %s</h3>
					</div>
					<div class="panel-body">
						%s
						%s
						%s
					</div>
				</div>',
				// anchor ID
				substr($function->getAttribute('id'), $compound_prefix_len),

				// Panel title = <type> <name> (<args>)
				$xpath->query("type", $function)->item(0)->nodeValue,
				$xpath->query("name", $function)->item(0)->nodeValue,
				$xpath->query("argsstring", $function)->item(0)->nodeValue,

				// panel content
				to_html($xpath->query("detaileddescription/para[not(parameterlist)]", $function)->item(0)->nodeValue),

				parameters_str( $xpath, $xpath->query("detaileddescription/para/parameterlist[@kind='param']/parameteritem", $function) ),

				return_values_str( $xpath, $xpath->query("detaileddescription/para/parameterlist[@kind='retval']/parameteritem", $function) )
			);

		}
	}
	// write MD file
	file_put_contents($md_file_path, $md_content);
}


echoArray( $md_content );


function parameters_str($xpath, $parameter_list){
	if( $parameter_list->length == 0 ) return "";
	$md_text = "<br/><br/>
				<bold>Parameters</bold></br></br>
				<dd>";
	$md_text .= '<table><tbody>';
	foreach( $parameter_list as $param_item ){
		$md_text .= sprintf(
			'<tr style="vertical-align:text-top;">
				<td><bold>%s</bold></td>
				<td style="width:8px"></td>
				<td><code>%s</code></td>
				<td style="width:8px"></td>',
				$xpath->query("parameternamelist/parametertype", $param_item)->item(0)->nodeValue,
				$xpath->query("parameternamelist/parametername", $param_item)->item(0)->nodeValue
		);
		$md_text .= sprintf(
				'<td>%s</td>
			</tr>',
			to_html($xpath->document->saveXML($xpath->query("parameterdescription", $param_item)->item(0)))
		);
	}
	$md_text .= '</tbody></table>';
	$md_text .= "</dd>";
	return $md_text;
}


function return_values_str($xpath, $parameter_list){
	if( $parameter_list->length == 0 ) return "";
	$md_text = "<br/><br/>
				<bold>Return values</bold></br></br>
				<dd>";
	$md_text .= '<table><tbody>';
	foreach( $parameter_list as $param_item ){
		$md_text .= sprintf(
			'<tr style="vertical-align:text-top;">
				<td><code>%s</code></td>
				<td style="width:8px"></td>',
				$xpath->query("parameternamelist/parametername", $param_item)->item(0)->nodeValue
		);
		$md_text .= sprintf(
				'<td>%s</td>
			</tr>',
			to_html($xpath->document->saveXML($xpath->query("parameterdescription", $param_item)->item(0)))
		);
	}
	$md_text .= '</tbody></table>';
	$md_text .= "</dd>";
	return $md_text;
}

function to_html($xml_str){
	$html_str = str_replace( 'computeroutput', 'code', $xml_str );
	$html_str = str_replace( 'preformatted', 'pre', $html_str );
	$html_str = str_replace( ['<parameterdescription>', '</parameterdescription>'], '', $html_str );
	$html_str = str_replace( '<para>', '<p>', $html_str );
	$html_str = str_replace( '</para>', '</p>', $html_str );
	//
	return $html_str;
}

?>
