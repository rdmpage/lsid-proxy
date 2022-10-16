<?php

error_reporting(E_ALL);

require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/lsid.php');

use Symfony\Component\Yaml\Yaml;

//----------------------------------------------------------------------------------------
// Read configuration file
function get_config($filename)
{
	$config = (object)(Yaml::parseFile($filename));
	
	if (0)
	{
		echo '<pre>';
		print_r($config);
		echo '</pre>';
	}

	return $config;
}

//----------------------------------------------------------------------------------------
function display_default()
{
	echo '<!DOCTYPE html><html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link type="text/css" href="main.css" rel="stylesheet">
</head>
<body>
<h1>Life Sciences Identifier (LSID) Resolver</h1>
<p>Resolve a <a href="https://en.wikipedia.org/wiki/LSID">LSID</a> by adding the LSID to
the URL for this resolver.</p>
<p>If you append <code>+</code> to the LSID the resolver
	 will display the LSID metadata, otherwise it will attempt to redirect 
	 you to the sourcr webpage for the LSID.</p>';

	$resolver = (!empty($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) == 'on' || $_SERVER['HTTPS'] == '1')) ? 'https://' : 'http://';
	$resolver .= $_SERVER['SERVER_NAME'];
	if (1)
	{
		$resolver .= $_SERVER['REQUEST_URI'];
	}
	
	$examples = array(
		'urn:lsid:ipni.org:names:99338-1',
		'urn:lsid:ipni.org:names:77209281-1',
		'urn:lsid:ipni.org:names:77153960-1',
		'urn:lsid:indexfungorum.org:names:356289',
		'urn:lsid:marinespecies.org:taxname:955176',
		'urn:lsid:nmbe.ch:spidersp:021946',
		'urn:lsid:Orthoptera.speciesfile.org:TaxonName:61777',
		'urn:lsid:organismnames.com:name:1776318',		
	);
	
	echo '<ul>';
	foreach ($examples as $example_lsid)
	{
		echo '<li>';

		echo '<a href="./' . $example_lsid . '">';
		echo $resolver . $example_lsid;
		echo '</a>';

		echo ' [';
		echo '<a href="./' . $example_lsid . '+">';
		echo '+';
		echo '</a>';
		echo ']';
		
		echo '</li>';
	
	}
	echo '</ul>';
	
	echo '</body>
</html>';
}

//----------------------------------------------------------------------------------------
function display_xml($response)
{
	switch ($response->status)
	{
		case 303:
			header('HTTP/1.1 303 See Other');
			break;
			
		case 400:
			header('HTTP/1.1 400 Bad request');
			break;

		case 404:
			header('HTTP/1.1 404 Not Found');
			break;
		
		case 410:
			header('HTTP/1.1 410 Gone');
			break;
		
		case 500:
			header('HTTP/1.1 500 Internal Server Error');
			break;

		case 501:
			header('HTTP/1.1 501 Not Implemented');
			break;
			
		case 200:
		default:
			header('HTTP/1.1 200 OK');
			break;
	}

	header("Content-Type: application/xml");
	
	if ($response->status == 200)
	{
		echo $response->rdf;
	}
	else
	{
		// error message
		$dom = new DOMDocument('1.0', 'utf-8');
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;	
		
		// root element is <response>
		$root = $dom->appendChild($dom->createElement('error'));

		$lsid_node = $root->appendChild($dom->createElement('lsid'));
		$lsid_node->appendChild($dom->createTextNode($response->lsid));

		$msg_node = $root->appendChild($dom->createElement('message'));
		$msg_node->appendChild($dom->createTextNode($response->msg));

		$status_node = $root->appendChild($dom->createElement('status'));
		$status_node->appendChild($dom->createTextNode($response->status));

		echo $dom->saveXML();
	}
}

//----------------------------------------------------------------------------------------
function display_html($response, $html_redirect = false)
{
	/*
	print_r($response);
	
	if ($html_redirect)
	{
		echo "redirect";
	}
	*/

	if ($html_redirect && ($response->status == 200) && isset($response->web))
	{
		// redirect to native web version of record
		header('HTTP/1.1 303 See Other');
		header("Location: " . $response->web);
		exit();
	}
	else
	{
		switch ($response->status)
		{
			case 303:
				header('HTTP/1.1 303 See Other');
				break;
			
			case 400:
				header('HTTP/1.1 400 Bad request');
				break;

			case 404:
				header('HTTP/1.1 404 Not Found');
				break;
		
			case 410:
				header('HTTP/1.1 410 Gone');
				break;
		
			case 500:
				header('HTTP/1.1 500 Internal Server Error');
				break;

			case 501:
				header('HTTP/1.1 501 Not Implemented');
				break;
			
			case 200:
			default:
				header('HTTP/1.1 200 OK');
				break;
		}

		header("Content-Type: text/html");
	
		if ($response->status == 200)
		{
			//echo $response->rdf;
		
			$xml = new DOMDocument();
			$xml->loadXML($response->rdf);
		
			$xsl = new DOMDocument;
			$xsl->load(dirname(__FILE__) . '/lsid.xsl');
 
			$proc = new XSLTProcessor();
			$proc->importStyleSheet($xsl);
 
			echo $proc->transformToXML($xml);
		}
		else
		{
			// error message		
			echo '<!DOCTYPE html><html>
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<link type="text/css" href="main.css" rel="stylesheet">
	</head>
	<body>
	<h1>LSID resolver</h1>
	<p class="error">
	';

	echo $response->lsid . ' ' . $response->msg;

	echo '</p>
	</body>
	</html>';
		}
	}
}

//----------------------------------------------------------------------------------------
function main()
{
	$config = get_config(dirname(__FILE__) . '/config.yml');
	
	$lsid 	= '';
	$format = 'xml';
	
	$html_redirect = true;
		
	if (isset($_GET['lsid']))
	{
		$lsid = $_GET['lsid'];
	}	

	if (isset($_SERVER['HTTP_ACCEPT']))
	{
		switch ($_SERVER['HTTP_ACCEPT'])
		{
			case 'application/rdf+xml':
			case 'application/xml':
				$format = 'xml';
				break;
		
			default:
				$format = 'html';
				break;
		}
	}
	
	if (isset($_GET['format']))
	{
		switch ($_GET['format'])
		{
			case 'xml':
				$format = $_GET['format'];
				break;
				
			default:
				$format = 'html';
				break;
		}
	}
	
	if (isset($_GET['noredirect']))
	{
		$html_redirect = false;
	}	
	
	if ($lsid != '')
	{	
		$response = new stdclass;
		$response->lsid = $lsid;
		$response->status = 404;	

		$lsid_object = new LSID($lsid);
	
		// Does it need special treatment (i.e., resolution mechanism is broken)?		
		$customResolver = null;
	
		foreach ($config->resolver as $resolver)
		{
			if ($resolver['domain'] == $lsid_object->getAuthority())
			{
				$customResolver = $resolver;
			}						
		}
	
		if ($customResolver)
		{
			// generate URL to retrieve metadata
		
			// URL includes full LSID
			if (preg_match('/\{LSID\}/', $customResolver['url']))
			{
				$customResolver['url'] = str_replace('{LSID}', $lsid, $customResolver['url']);
			}

			// URL includes just local identifier
			if (preg_match('/\{ID\}/', $customResolver['url']))
			{
				$customResolver['url'] = str_replace('{ID}', $lsid_object->object, $customResolver['url']);				
			}
		
			$response = customResolveLSID($lsid, $customResolver, $config);
			
			if (isset($customResolver['web']))
			{
				$response->web = $customResolver['web'];
				
				if (preg_match('/\{LSID\}/', $response->web))
				{
					$response->web = str_replace('{LSID}', $lsid, $response->web);
				}
				
				if (preg_match('/\{ID\}/', $response->web))
				{
					$response->web = str_replace('{ID}', $lsid_object->object, $response->web);
				}
				
			}
			
		}
		else
		{
			// attempt to resolve using LSID protocol
			$response = resolveLSID($lsid, $config);
		}
	
		switch ($format)
		{
			case 'xml':
				display_xml($response);
				break;
			
			case 'html':
			default:
				display_html($response, $html_redirect);
				break;		
		}
	}
	else
	{
		display_default();
	}		

}

main();

?>
