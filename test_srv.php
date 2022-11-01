<?php

$hosts = array(

'algaebase.org',
'Aphid.speciesfile.org',

'Blattodea.speciesfile.org',

'Coreoidea.speciesfile.org',

'gbif.org',

'indexfungorum.org',
'ipni.org',
'irmng.org',
'itis.gov',

'marinespecies.org',

'organismnames.com',
'Orthoptera.speciesfile.org',

'nmbe.ch',

'zoobank.org',

);

$hosts = array(
'biocol.org',
);

foreach ($hosts as $host)
{
	$result = dns_get_record('_lsid._tcp.' . $host, DNS_SRV);
	
	print_r($result);
}


?>
