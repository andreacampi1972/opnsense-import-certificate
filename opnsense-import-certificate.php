<?php

/**
 * Import SSL certificates from a pre-determined place on the filesystem.
 * Once imported, set them for use in the GUI
 */

if (empty($argc)) {
	echo "Only accessible from the CLI.\r\n";
	die(1);
}

if ($argc != 4) {
	echo "Usage: php " . $argv[0] . " /path/to/certificate.crt /path/to/private/key.pem cert_hostname.domain.tld\r\n";
	die(1);
}

require_once "config.inc";
require_once "certs.inc";
require_once "util.inc";
require_once "filter.inc";

$certificate = trim(file_get_contents($argv[1]));
$key = trim(file_get_contents($argv[2]));
$hostname = trim($argv[3]);

// Do some quick verification of the certificate, similar to what the GUI does
if (empty($certificate)) {
	echo "The certificate is empty.\r\n";
	die(1);
}
if (!strstr($certificate, "BEGIN CERTIFICATE") || !strstr($certificate, "END CERTIFICATE")) {
	echo "This certificate does not appear to be valid.\r\nOr: cert and privkey args switched?\r\n";
	die(1);
}

// Verification that the certificate matches
if (empty($key)) {
	echo "The key is empty.\r\n";
	die(1);
}

$cert = array();
$cert['refid'] = uniqid();
$cert['descr'] = "$hostname";

cert_import($cert, $certificate, $key);

// Set up the existing certificate store
// Copied from system_certmanager.php
if (!is_array($config['ca'])) {
	$config['ca'] = array();
}

$a_ca =& $config['ca'];

if (!is_array($config['cert'])) {
	$config['cert'] = array();
}

$a_cert =& $config['cert'];


// Check if the certificate we just parsed is already imported using description and substitute it with the new one

foreach ($a_cert as &$existing_cert) {
	if ($existing_cert['descr'] === $cert['descr']) {
			$existing_cert['crt'] = $cert['crt'];
			$existing_cert['prv'] = $cert['prv'];
			break;
	}
}

// Write out the updated configuration
write_config();

log_error('Web GUI configuration has changed. Restarting now.');
configd_run('webgui restart 2', true);

echo "Completed! New certificate Updated installed.\r\n";
