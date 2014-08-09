<?php
// --
error_reporting(E_ALL);
ini_set("display_errors", 1);
ini_set("highlight.comment", "#008040;");
ini_set("highlight.string", "#0000ff;");
ini_set("highlight.keyword", "#800040; font-weight: bold;");
ini_set("highlight.default", "#000;");
require("Builder/aBuilder.php");
// --
$r = array();
foreach (array("BuildVersionBuilder", "MinifiedVersionBuilder") as $builder)
	{
	require("Builder/" . $builder . ".php");
	$builder	= new $builder();
	$id			= "id." . sha1(serialize($builder));
	$r[]		= "<div>";
	if ($builder->build() && $builder->save())
		{
		$r[] = "<h2>" . get_class($builder) . "</h2>";
		$r[] = 
			"<span style=\"color: #008000;\">SUCCESS: " .
				"<a href=\"javascript:void(0);\" onclick=\"var e = document.getElementById('" . $id . "'); e.style.display = (e.style.display == 'none' ? 'block' : 'none');\">" . 
					$builder->getTarget() . 
				"</a>" .
			"</span>" . 
			"<div id=\"" . $id . "\" style=\"display: none; border: 1px solid silver; margin-top: 1em; padding: 0.5em;\">" .
				highlight_string($builder->getSource(), true) . 
			"</div>";
		}
	else
		{
		$r[] =  "" .
			"<span style=\"color: #ae0000;\">FAILED: " . 
				$builder->getTarget() .
			"</span>";
		}
	}
echo "<!DOCTYPE HTML><html><head></head><body style=\"font-family: monospace;\"><h1>CssMin Build</h1>" . join($r) . "</body><html>";
die;
?>