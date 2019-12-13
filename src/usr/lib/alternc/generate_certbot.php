#!/usr/bin/php
<?php
/**
  * Retrieves or renews certs 
  * using Letsencrypt and ACME 1.0 protocol, with HTTP validation
  * Called as a cron or as an interactive script during alternc.install
  *
  * usage: [-v | --verbose] [-c TYPE | --certificates TYPE] [-d DOMAIN1,DOMAIN2 | --domains DOMAIN1,DOMAIN2] [-f | --force]
  *   --verbose            display progress information (default = quiet)
  *   --certificates TYPE  which type of certificates to request: all, system, non-system
  *   --domains            A comma separated list of specific domains to request
  *   --force              Force the renewal request to happen (only when renewing specific domains)
  *
  * certificates defaults to "all", or the value of the environment variable
  * ALTERNC_REQUEST_CERTIFICATES (eg. in /etc/alternc/local.sh).
  */

// Renew a domain if we don't have a cert or if it expires $VALID_DAYS from now
$VALID_DAYS = 30;
// Use the difference in seconds to avoid re-calculating the value for each sub-domain
$VALID_DIFF = 86400 * 30;

// Which type of certificates should be requested: all, system, non-system.
$REQUEST_CERTS="all";
$ALLOWED_CERT_TYPES = array(
    'all',
    'system',
    'non-system',
    'specific', // This is just used to differentiate the case of requesting
    // only one (or more) specific domains.
);

// Use the environment variable as the default if it set.
if (getenv('ALTERNC_REQUEST_CERTIFICATES') !== FALSE) {
    $REQUEST_CERTS = getenv('ALTERNC_REQUEST_CERTIFICATES');
}

$short_options = 'vc:d:f';
$long_options = array(
    'verbose',
    'certificates:',
    'domains:',
    'force',
);
$options = getopt($short_options, $long_options);

$verbose = (in_array('v', array_keys($options)) || in_array('verbose', array_keys($options))) ? True : False;

// Use --certificates if it is set in priorirty, otherwise choose '-c', or if that
// isn't set, use the default value.
$REQUEST_CERTS= in_array('certificates', array_keys($options)) ? $options['certificates'] : (
    in_array('c', array_keys($options)) ? $options['c'] : $REQUEST_CERTS);
$domains = in_array('domains', array_keys($options)) ? $options['domains'] : (
    in_array('d', array_keys($options)) ? $options['d'] : NULL);
if ($domains !== NULL) {
    $domains = explode(',', $domains);
    $REQUEST_CERTS = 'sepcific';
}
$force_request = (in_array('f', array_keys($options)) || in_array('force', array_keys($options))) ? True : False;

function vprint( $message, $params ){
    global $verbose;
    if( $verbose ) {
        echo vsprintf( "$message", $params );
    }
}

if (!in_array($REQUEST_CERTS, $ALLOWED_CERT_TYPES)) {
    printf(_("Error: Requested certificate type '%s' not one of the allowed types: %s"),
           $REQUEST_CERTS, print_r($ALLOWED_CERT_TYPES, TRUE));
    exit(1);
}

// Bootstrap without session check
chdir("/usr/share/alternc/panel/");
require("/usr/share/alternc/panel/class/config_nochk.php");

// we become AlternC admin
$admin->enabled=1;

// get all vhost-type domaines types:
$is_vhost=array();
$types = $dom->domains_type_lst();
foreach($types as $type=>$data) {
    $is_vhost[$type]=($data["only_dns"]==0);
}

$spacer="                                                                                 ";

// Request system domains before user certificates.
if ($REQUEST_CERTS == 'all' || $REQUEST_CERTS == 'system') {
    foreach($ssl->get_fqdn_specials() as $specialfqdn) {
        vprint( _("\r$spacer\rRequesting domain %s"), array( $specialfqdn ));
        if( ! $certbot->isLocalAlterncDomain( $specialfqdn ) ){
            continue;
        }
        vprint( _(" hosted locally, running certbot..."), array( ));

        $certbot->import($specialfqdn);
    }
    vprintf(_("\rFinished renewal for system certificates\n"), array());
}
else {
    vprint(_("Skipping system certificates, requested certificates type: %s\n"), array($REQUEST_CERTS));
}

if ($REQUEST_CERTS == 'specific' && $domains !== NULL) {
    foreach ($domains as $domain) {
        $current = $ssl->get_valid_certs($domain, 'letsencrypt');
        // @HACK @WIP
        // Request the certificate if there are 2 or fewer results (the snakeoils),
        // or if force_request is set.
        if (sizeof($current) <= 2  || $force_request) {
            $certbot->import($domain);
        }
    }
}
elseif ($REQUEST_CERTS == 'all' || $REQUEST_CERTS == 'non-system') {
    // Get all alternc accounts
    $accounts = $admin->get_list(1, 0, false, 'domaine');

    // Retrieve every information of every subdomains from user accounts
    // (only those for which only_dns is false (they have vhosts)
    $domainsList = array();
    foreach ($accounts as $cuid => $infos) {
        $mem->su($cuid);
        // Get all domain set to each user
        $domains = $dom->enum_domains();
        foreach ($domains as $domain) {
            $dom->lock();
            $domain_data = $dom->get_domain_all($domain);
            // Get all hosts (subdomain)
            if (!isset($domain_data['sub'])) {
                continue;
            }
            foreach ($domain_data['sub'] as $sub_domain) {
                if (in_array($sub_domain['type'], $is_vhost) &&
                    $is_vhost[strtolower($sub_domain["type"])]) {
                    $domainsList[] = array('sub_domain' => $sub_domain, 'cuid' => $cuid);
                }
            }
            $dom->unlock();
        }
        $mem->unsu();
    }

    // Need to request anything:
    if(  count( $domainsList ) ){

        vprint( _("Requiring Certbot renewal for %s domains\n"), count( $domainsList ));
        foreach ($domainsList as $key => $sub_domain) {
            $mem->su($sub_domain["cuid"]);
            // Check if we already have a valid cert for this domain (valid for more than $VALID_DAYS days
            // Either the subdomain (first, quicker), or any Certificate found for this FQDN
            if (isset($sub_domain['sub_domain']["certificate_id"])) {

                // trick below: false=>not found, 0 => Snakeoil == skip both
                if ( $current = $ssl->get_certificate($sub_domain["sub_domain"]["certificate_id"],true) ) { 

                    // found, is it valid ? (fqdn match) (skips panel one)
                    if ($ssl->fqdnmatch($current["fqdn"],$sub_domain["sub_domain"]["fqdn"])) {
                        // found and valid, (works for wildcards too ;) )
                        // now what about the date?
                        $t = time();
                        if ($current['validstartts'] < $t &&
                            $t < ($current['validendts'] - $VALID_DIFF)) {
                            // currently valid, and valid for more than $VALID_DAYS
                            // let's skip this one for now
                            continue;
                        }
                    }
                }
            }

            // not found or invalid or expired soon, let's get one:
            vprint( _("\r$spacer\rRequesting domain %d/%d: %s"), array( $key + 1, count( $domainsList),$sub_domain["sub_domain"]["fqdn"] )); 
            if( ! $certbot->isLocalAlterncDomain( $sub_domain["sub_domain"]["fqdn"] ) ){
                continue;
            }
            vprint( _(" hosted locally, running certbot..."), array( ));
            $certbot->import($sub_domain["sub_domain"]["fqdn"]);
        }
        vprint( _("\nFinished Certbot renewal for non-system certificates"), count( $domainsList ));
    } else {
        vprint( _("\nNo standard Certbot renewal for non-system-certificates"), count( $domainsList ));
    }
}
else {
    vprint(_("Skipping non-system certificates, requested certificates type: %s"), array($REQUEST_CERTS));
}

vprint( _("\nFinished Certbot renewal\n"), array());
