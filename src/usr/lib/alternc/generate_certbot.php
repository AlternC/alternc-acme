#!/usr/bin/php
<?php
/**
  * Retrieves or renews certs 
  * using Letsencrypt and ACME 1.0 protocol, with HTTP validation
  * Called as a cron or as an interactive script during alternc.install
  *
  * usage: -v | --verbose  display progress information (default = quiet)
  *
  */

// Renew a domain if we don't have a cert or if it expires $VALID_DAYS from now
$VALID_DAYS = 30; 

// Handle the verbose flag
$verbose = ( $argc > 1 && in_array( $argv[1], array( "-v", "--verbose")  ) ) ? True : False;
function vprint( $message, $params ){
    global $verbose;
    if( $verbose ) {
        echo vsprintf( "$message", $params );
    }
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
        foreach ($domain_data['sub'] as $sub_domain) {
            if ($is_vhost[$sub_domain["type"]]) {
                $domainsList[] = array('sub_domain' => $sub_domain, 'cuid' => $cuid);
            }
        }
        $dom->unlock();
    }
    $mem->unsu();
}
$spacer="                                                                                 ";

// Need to request anything: 
if(  count( $domainsList ) ){

    vprint( _("Requiring Certbot renewal for %s domains\n"), count( $domainsList )); 
    
    foreach ($domainsList as $key => $sub_domain) {
        $mem->su($sub_domain["cuid"]);
        // Check if we already have a valid cert for this domain (valid for more than $VALID_DAYS days
        // Either the subdomain (first, quicker), or any Certificate found for this FQDN
        if (isset($sub_domain['domain']["certificate_id"])) {

            // trick below: false=>not found, 0 => Snakeoil == skip both
            if ( $current = $ssl->get_certificate($sub_domain["sub_domain"]["certificate_id"],true) ) { 

                // found, is it valid ? (fqdn match) (skips panel one) 
                if ($ssl->fqdnMatch($current["fqdn"],$sub_domain["sub_domain"]["fqdn"])) {
                    // found and valid, (works for wildcards too ;) )
                    // now what about the date?
                    if ($current["validstartts"]>time()
                    && $current["validendts"]>(time()+(86400*$VALID_DAYS))
                    ) {
                        // valid at least for $VALID_DAYS from now, let's skip this one for now
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
    vprint( _("\nFinished Certbot renewal, now doing system certs\n"), count( $domainsList ));
} else {
    vprint( _("\nNo standard Certbot renewal to do, now doing system certs\n"), count( $domainsList ));
}

/* Also create TLS certificates for system FQDN (panel, dovecot, postfix, proftpd, mailman ... */
foreach($ssl->get_fqdn_specials() as $specialfqdn) {
    vprint( _("\r$spacer\rRequesting domain %s"), array( $specialfqdn ));
    if( ! $certbot->isLocalAlterncDomain( $specialfqdn ) ){
        continue;
    }
    vprint( _(" hosted locally, running certbot..."), array( ));

    $certbot->import($specialfqdn);
}
vprint( _("\nFinished Certbot renewal\n"), count( $domainsList ));

