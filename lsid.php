<?php

//----------------------------------------------------------------------------------------
/**
 *@brief Encapsulate a LSID
 *
 */

class LSID {

	var $lsid;
	var $authority;
	var $namespace;
	var $object;
	var $revision;
	
	
	//------------------------------------------------------------------------------------
	function __construct ($urn)
	{
		$this->lsid = $urn;
		if ($this->isValid())
		{
			$this->components();
		}
				
	}
	
	//------------------------------------------------------------------------------------
	function asString ()
	{
		return $this->lsid;
	}

	//------------------------------------------------------------------------------------
	/**
	 * @brief Test whether LSID is syntactically correct.
	 *
	 * Uses a regular expression taken from IBM's Perl stack.
	 *
	 * @return True if LSID is valid. 
	 *
	 */
	function isValid()
	{
		return preg_match ("/^[uU][rR][nN]:[lL][sS][iI][dD]:[A-Za-z0-9][\w\(\)\+\,\-\.\=\@\;\$\"\!\*\']*:[A-Za-z0-9][\w\(\)\+\,\-\.\=\@\;\$\"\!\*\']*:[A-Za-z0-9][\w\(\)\+\,\-\.\=\@\;\$\"\!\*\']*(:[A-Za-z0-9][\w\(\)\+\,\-\.\=\@\;\$\"\!\*\']*)?$/", $this->lsid);
	}
	
	//------------------------------------------------------------------------------------
	/**
	 * @brief Extract component parts of LSID.
	 *
	 */
	function components()
	{		
		$components = explode(':', $this->lsid);
		$this->authority = strtolower($components[2]);
		$this->namespace = $components[3];
		$this->object = $components[4];
		if (isset($components[5]))
		{
			$this->revision = $components[5];
		}
		else
		{
			$this->revision = '';
		}
	}
	
	//------------------------------------------------------------------------------------
	function getAuthority()
	{
		return $this->authority;
	}

}

//----------------------------------------------------------------------------------------
/**
 *@brief Encapsulate a LSID authority
 *
 */
class Authority {

	var $server;
	var $port;
	var $wsdl;
	var $service_wsdl;
	var $httpBinding;
	var $httpMetadataBinding;
	var $http_code;
	var $lsid_code;
	var $curl_code;
	var $debug;
	var $lsid;
	var $lsid_error_code;
	var $header_counter;
	var $stored_wsdl;
	
	var $config;

	//------------------------------------------------------------------------------------
	function __construct ($configuration = null)
	{
		if ($configuration)
		{
			$this->config = $configuration;
		}
		else
		{
			// make a default config
			$this->config = new stdclass;
			$this->config->cache_path = dirname(__FILE__) . '/cache';
			$this->config->cache_time = 3600;
		}
		
		$this->server 	= '';
		$this->port 	= '';
		
		$this->debug = false;
		
		$this->lsid_error_code = 0;	
	}
	
	//------------------------------------------------------------------------------------
	/**
	 * @brief Resolve a LSID using the DNS
	 *
	 * 
	 */
	function Resolve ($lsid_to_resolve)
	{
		$result = true;
		
		$this->lsid = new LSID($lsid_to_resolve);
		
		// attenpt to find resolver using DNS SRV		
		$lookup = dns_get_record('_lsid._tcp.' . $this->lsid->getAuthority(), DNS_SRV);
		if (count($lookup) == 0)
		{
			$result = false;
		}
		else
		{
			$this->server = $lookup[0]['target'];
			$this->port = $lookup[0]['port'];
		}
				
		return $result;
	}
	
	//------------------------------------------------------------------------------------
	function GetLocation ()
	{
		return $this->server . ":" . $this->port;
	}
	
	//------------------------------------------------------------------------------------
	/**
	 * @brief Test whether HTTP code is valid
	 *
	 * HTTP codes 200 and 302 are OK.
	 *
	 * @param HTTP code
	 *
	 * @result True if HTTP code is valid
	 */

	function HttpCodeValid($http_code)
	{
		if ( ($http_code == '200') || ($http_code == '302') ){
			return true;
		}
		else{
			return false;
		}
	}
	
	//------------------------------------------------------------------------------------
	/**
	 * @brief Get a WSDL using HTTP GET
	 *
	 * @param url the URL for the WSDL
	 *
	 * @result If successful returns the WSDSL, otherwise empty string
	 */
	function GetWSDL ($url)
	{
		$result = '';
		$this->lsid_error_code = 0;
		
		$wsdl = '';
		$ch = curl_init(); 
		curl_setopt ($ch, CURLOPT_URL, $url); 
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 	1); 
		curl_setopt ($ch, CURLOPT_HEADER,			1); 
		curl_setopt ($ch, CURLOPT_FOLLOWLOCATION,	1); 
		
		
		if (isset($this->config->http_proxy))
		{
			curl_setopt ($ch, CURLOPT_PROXY, $this->config->http_proxy);
		}
		
		$wsdl=curl_exec ($ch); 
		
		if( curl_errno ($ch) != 0 )
		{
			$curl_code = curl_errno ($ch);
		}
		else
		{
			 $info = curl_getinfo($ch);
			 
			 $header = substr($wsdl, 0, $info['header_size']);
			 
			 $this->http_code = $info['http_code'];

			 if ($this->HttpCodeValid ($this->http_code))
			 {
			 	// Everything seems OK			 
			 }
			 else
			 {			 	
			 	// Extract LSID error code, if any			 	
				$rows = explode ("\n", $header);
				foreach ($rows as $row)
				{
					$parts = explode (":", $row, 2);
					if (count($parts) == 2)
					{
						if (preg_match("/LSID-Error-Code/", $parts[0]))
						{
							$this->lsid_error_code = $parts[1];
						}
					}
				}
			 }
			 if (($this->HttpCodeValid ($this->http_code)) && ($this->lsid_error_code == 0))
			 {
				$wsdl = substr ($wsdl,$info['header_size']);
				$result = $wsdl;
			}
		}
		curl_close ($ch); 
		
		return $result;
	}
	
	//------------------------------------------------------------------------------------
	/**
	 * @brief Store a WSDL in the disk cache
	 *
	 * @param name Filename for the WSDL
	 *
	 */
	function StoreWSDLInCache ($name)
	{
		$cache_authority = $this->config->cache_path . "/" . $this->lsid->authority;
		$cache_filename = $cache_authority . "/" . $name;
				
		// Ensure cache subfolder exists for this authority
		if (!file_exists($cache_authority))
		{
			$oldumask = umask(0); 
			mkdir($cache_authority, 0777);
			umask($oldumask);
		}
		
		// Store data in cache
		$cache_file = @fopen($cache_filename, "w+") or die("could't open file --\"$cache_filename\"");
		
		if ($name == 'authority.wsdl')
		{
			@fwrite($cache_file, $this->wsdl);
		}
		else
		{
			@fwrite($cache_file, $this->service_wsdl);
		}
		fclose($cache_file);
	}
	
	
	//------------------------------------------------------------------------------------
	/**
	 * @brief Get WSDL from the disk cache
	 *
	 * @param name Filename for the WSDL
	 *
	 * @result If WSDL name is in cache return WSDL, otherwise empty string
	 *
	 */
	function GetWSDLFromCache ($name)
	{
		$wsdl = '';
		
		$cache_authority = $this->config->cache_path . "/" . $this->lsid->authority;
		$cache_filename = $cache_authority . "/" .$name;
				
		// Does cache subfolder exist for this authority?
		if (file_exists($cache_authority))
		{
			if (file_exists($cache_filename))
			{
				// How old is it?
				$Diff = time() - filemtime ($cache_filename);
				
				if ($Diff > $this->config->cache_time)
				{
					// Cached file is now too old so delete it
					unlink($cache_filename);
				}
				else
				{
					// Load data from cache
					$cache_file = @fopen($cache_filename, "r") or die("could't open file \"$cache_filename\"");
					$wsdl = @fread($cache_file, filesize ($cache_filename));
					fclose($cache_file);
				}
	
			}
		}
		return $wsdl;		
	}


	//------------------------------------------------------------------------------------
	/**
	 * @brief Get authority WSDL
	 *
	 * We first look up WSDL in the cache, if it's not there we go to the
	 * authority itself.
	 *
	 * @return True of successful
	 */
	function GetAuthorityWSDL ()
	{
		$result = true;
				
		if ($this->config->cache_time > 0)
		{
			if ($this->debug)
			{
				echo "Trying cache...";
			}		
		
			// Try the cache first
			$this->wsdl = $this->GetWSDLFromCache('authority.wsdl');
			
			if ($this->debug)
			{
				if ($this->wsdl == '')
				{
					echo "not found";
				}
				else
				{
					echo "found";
				}
				echo "\n";
			
			} 
		
		}
		
		if ($this->wsdl == '')
		{
			// Get live copy
			$url = $this->server . ":" .  $this->port . "/authority/";
			
			$this->wsdl = $this->GetWSDL ($url);
			
			$result = ($this->wsdl != '');
			
			if ($result)
			{
				$this->StoreWSDLInCache('authority.wsdl');
			}
			
			if ($this->debug)
			{
				echo "Authority WSDL retrieved from ", $url, "\n";
			}
		}		

		if ($this->debug)
		{
			echo "Authority WSDL:\n";
			echo $this->wsdl . "\n";
		}

		return $result;
	
	}
	
	//------------------------------------------------------------------------------------
	/**
	 * @brief Get WSDL describing services
	 *
	 * @param l LSID
	 *
	 * @result True if successful
	 *
	 */
	function GetServiceWSDL ($l)
	{
		$result = true;
		
		if ($this->config->cache_time > 0)
		{
			if ($this->debug)
			{
				echo "Trying cache...";
			}
				
			// Try the cache first
			$this->service_wsdl = $this->GetWSDLFromCache('service.wsdl');
			
			$result = ($this->service_wsdl != '');

			
			if ($this->debug)
			{
				if ($this->service_wsdl == '')
				{
					echo "not found";
				}
				else
				{
					echo "found";
				}
				echo "\n";
			
			} 
			
		}
		
		if ($this->service_wsdl == '')
		{
			// Get live copy
			$url = $this->httpBinding . "/authority/?lsid=" . $l;
						
			$this->service_wsdl = $this->GetWSDL ($url);
			
			$result = ($this->service_wsdl != '');
			
			$this->StoreWSDLInCache('service.wsdl');
			
			if ($this->debug)
			{
				echo "Service WSDL retrieved from ", $url, "\n";
			}
		}		

		if ($this->debug)
		{
			echo "Service WSDL:\n";
			echo $this->service_wsdl . "\n";
		}
		
		return $result;

	}


	//------------------------------------------------------------------------------------
	/**
	 * @brief Get HTTP binding for LSID authority
	 *
	 */
	function GetHTTPBinding ()
	{
		$this->httpBinding = '';
	
		$dom= new DOMDocument;
		$dom->loadXML($this->wsdl);
						
		$xpath = new DOMXPath($dom);
		$xpath->registerNamespace('wsdl', 'http://schemas.xmlsoap.org/wsdl/');
		$xpath->registerNamespace('httpsns', 'http://www.omg.org/LSID/2003/AuthorityServiceHTTPBindings');
				
		$nodeCollection = $xpath->query ('//wsdl:service/wsdl:port/httpsns:address/@location');
		foreach($nodeCollection as $node)
		{
			$this->httpBinding = $node->firstChild->nodeValue;
			$this->httpBinding = rtrim($this->httpBinding, "/");
		}	

		$result = ($this->httpBinding != '');
		return $result;
		
	}
	
	//------------------------------------------------------------------------------------
	/*
	 * @brief Check that HTTP binding for authority is a server address (i.e., nothing after http://my.web.com[:80]/)
	 *
	 * @return True if HTTP binding is server address.
	 *
	 */
	function bindingIsServerAddress ()
	{
		return 	preg_match ("/^https?:\/\/([a-z0-9\-]*[a-z0-9]?\.)+(?:com|edu|biz|org|gov|int|info|mil|net|name|museum|coop|aero|[a-z][a-z])(:[0-9]{2,4})?(\/?)$/",  $this->httpBinding);
	}

	//------------------------------------------------------------------------------------
	/*
	 * @brief Get HTTP access point 
	 *
	 * @return True successful.
	 *
	 */
	function GetMetadataHTTPLocation()
	{

		$this->httpMetadataBinding = '';
		
		$wsdl = $this->service_wsdl;
		
		$dom= new DOMDocument;
		$dom->loadXML($wsdl);
		
		$xpath = new DOMXPath($dom);
		$xpath->registerNamespace('wsdl', 'http://schemas.xmlsoap.org/wsdl/');
		$xpath->registerNamespace('http', 'http://schemas.xmlsoap.org/wsdl/http/');
		$xpath->registerNamespace('httpsns', 'http://www.omg.org/LSID/2003/http://www.omg.org/LSID/2003/DataServiceHTTPBindings');

		$nodeCollection = $xpath->query ('//wsdl:service/wsdl:port[@binding="httpsns:LSIDMetadataHTTPBinding"]/http:address/@location');
		foreach($nodeCollection as $node)
		{
			$this->httpMetadataBinding = $node->firstChild->nodeValue;
			$this->httpMetadataBinding = rtrim($this->httpMetadataBinding, "/");
		}	

		$result = ($this->httpMetadataBinding != '');
		return $result;
	}


	//------------------------------------------------------------------------------------
	/*
	 * @brief Get HTTP metadata access point
	 *
	 * @return Metadata if successful, otherwise return empty string
	 *
	 */
	function GetHTTPMetadata ($l)
	{
		$metadata = '';
		$this->lsid_error_code = 0;
				
		if ($this->config->cache_time > 0)
		{
			if ($this->debug)
			{
				echo "Trying cache...";
			}		
		
			// Try the cache first
			$cache_filename = $this->GetMetadataFilename();
			
			if (file_exists($cache_filename))
			{
				$metadata = file_get_contents($cache_filename);			
			}
		}
		
		if ($metadata == '')
		{
			$url = $this->httpMetadataBinding . "?lsid=" . $l;
				
			$ch = curl_init(); 
			curl_setopt($ch, CURLOPT_URL, $url); 
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 	1); 
			curl_setopt ($ch, CURLOPT_HEADER,		  	1); // debugging, show header
			curl_setopt ($ch, CURLOPT_FOLLOWLOCATION,	1); 

			if (isset($this->config->http_proxy))
			{
				curl_setopt ($ch, CURLOPT_PROXY, $this->config->http_proxy);
			}
						
			$curl_result = curl_exec ($ch); 
		
			if((curl_errno ($ch) != 0 ) && (curl_errno ($ch) != 18))
			{
				$this->curl_code = curl_errno ($ch);
			
				echo $url;
			}
			else
			{		
				 $info = curl_getinfo($ch);
				 $header = substr($curl_result, 0, $info['header_size']);
			 
				 $this->http_code = $info['http_code'];

				 if ($this->HttpCodeValid ($this->http_code)	)		 
				 {
					// Everything seems OK			 
				 }
				 else
				 {
					// Extract LSID error code, if any
					$this->lsid_error_code = 0;
					$rows = explode ("\n", $header);
					foreach ($rows as $row)
					{
						$parts = explode (":", $row, 2);
						if (count($parts) == 2)
						{
							if (preg_match("/LSID-Error-Code/", $parts[0]))
							{
								$this->lsid_error_code = $parts[1];
							}
						}
					}
				 }
	
				$metadata = substr ($curl_result, $info['header_size']);
				$this->CacheMetadata($metadata);
			}
			curl_close ($ch); 
		}
		
		return $metadata;
	}
	
	
	//------------------------------------------------------------------------------------
	function GetMetadataFilename()
	{
		$extension = 'rdf';
		
		$filename = $this->lsid->asString();
		$filename = str_replace(':', '-', $filename);
		
		$filename .= "." . $extension;
		$cache_authority = $this->config->cache_path . "/" . $this->lsid->authority;
		$cache_filename = $cache_authority . "/" . $filename;
		

		// Ensure cache subfolder exists for this authority
		if (!file_exists($cache_authority))
		{
			$oldumask = umask(0); 
			mkdir($cache_authority, 0777);
			umask($oldumask);
		}

		return $cache_filename;
	}
	
	//------------------------------------------------------------------------------------
	/*
	 * @brief Cache the metadata
	 *
	 * Fiilename is generated using MD5 hash and appending ".rdf"
	 *
	 * @return Filename 
	 *
	 */
	function CacheMetadata ($metadata)
	{	
		$cache_filename = $this->GetMetadataFilename();
			
		// Store data in cache
		$cache_file = @fopen($cache_filename, "w+") or die("could't open file \"--$cache_filename\"");
		@fwrite($cache_file, $metadata);
		fclose($cache_file);
	}
	
	
}

//----------------------------------------------------------------------------------------
/*
 * @brief Resolve an LSID
 *
 * Resolve LSID and return metadata
 *
 * @result If successful return metadata (RDF), otheerwise return XML formatted
 * information on why resolution failed.
 *
 */
function resolveLSID ($lsidstring = 'urn:lsid:nmbe.ch:spidersp:021946', $config = null)
{
	$response = new stdclass;
	
	$response->lsid = $lsidstring;
	$response->msg = "Not found";
	$response->status = 404;
			
	$lsid = new LSID($lsidstring);
	$authority = new Authority($config);
	
	$state = 0;
		
	while ($state != 100)
	{
		switch ($state)
		{
			case 0:
				if ($lsid->isValid())
				{
					$state = 1;
				}
				else
				{
					$response->status = 400;
					$response->msg = "LSID is not validly formed";
					$state = 100;
				}
				break;
				
				
			case 1:
				if ($authority->Resolve($lsid->asString()))
				{
					$state = 2;
				}
				else
				{
					$response->status = 404;
					$response->msg = "DNS lookup for SRV record for " . $lsid->getAuthority() . " failed";	
					$state = 100;					
				}
				break;
				
			case 2:
				if ($authority->GetAuthorityWSDL())
				{
					$state = 3;
				}
				else
				{
					$response->status = 504;
					$response->msg = "Error retrieving authority WSDL";	
					$state = 100;					
				}
				break;

			case 3:
				if ($authority->GetHTTPBinding())
				{
					$state = 4;
				}
				else
				{
					$response->status = 501;
					$response->msg = "No HTTP binding found";	
					$state = 100;					
				}
				break;

			case 4:
				if ($authority->GetServiceWSDL($lsid->asString()))
				{
					$state = 5;
				}
				else
				{
					$response->status = 501;
					$response->msg = "Error retrieving service WSDL";	
					$state = 100;					
				}
				break;
				
			case 5:
				$authority->GetMetadataHTTPLocation();
				$rdf = $authority->GetHTTPMetadata($lsid->asString());
				
				if ($rdf != '')
				{
					$response->status = 200;
					$response->rdf = $rdf;				
				}
				else
				{
					$response->status = 406;
					$response->msg = "No metadata found";					
				}
				
				$state = 100;
				break;

			case 100:
				break;		
		}
	}
	
	
	return $response;
}

//----------------------------------------------------------------------------------------
// Resolve LSID using approach specific to this LSID
function customResolveLSID ($lsidstring, $resolver, $config = null)
{
	$response = new stdclass;
	
	$response->lsid = $lsidstring;
	$response->status = 404;
			
	$lsid = new LSID($lsidstring);
	$authority = new Authority($config);
	
	$authority->lsid = $lsid;
	
	// Try the cache first
	$cache_filename = $authority->GetMetadataFilename();
	
	if (file_exists($cache_filename))
	{
		$response->status = 200;
		$response->rdf = file_get_contents($cache_filename);			
	}
	else
	{
		$format = '';
		
		if (isset($resolver['content-type']))
		{
			$format = $resolver['content-type'];
		}
	
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $resolver['url']);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	
		if ($format != '')
		{
			curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: " . $format));	
		}
	
		$result = curl_exec($ch);
		if($result == FALSE) 
		{
			$errorText = curl_error($ch);
			curl_close($ch);
			die($errorText);
		}
	
		$info = curl_getinfo($ch);
		
		$response->status = $info['http_code'];
		
		if ($response->status == 200 && preg_match('/^\s*<\?xml/', $result))
		{
			// XML? If yes then save it
			$response->rdf = $result;
			file_put_contents($cache_filename, $response->rdf);
		}
		else
		{
			$response->msg = "Not found";
			$response->status = 404;
		}
	}
	
	return $response;
}


if (0)
{
	$response = resolveLSID();
	
	print_r($response);
}
	

?>
