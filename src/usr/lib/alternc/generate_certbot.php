#!/usr/bin/php
<?php
/**
  * Retrieves or renews certs 
  *
  * Called as a cron or as an interactive script during alternc.install
  *
  * params : -v | --verbose  display user information
  *
  */

// Handle the verbose flag
$verbose = ( $argc > 1 && in_array( $argv[1], array( "-v", "--verbose")  ) ) ? True : False;
function vprint( $message, $params ){
    global $verbose;
    if( $verbose ) {
        echo vsprintf( "$message", $params );
    }
}

// Ne verifie pas ma session :)
chdir("/usr/share/alternc/panel/");
require("/usr/share/alternc/panel/class/config_nochk.php");

// we goes root 
$admin->enabled=1;

// Get all alternc accounts
$accounts = $admin->get_list(1, 0, false, 'domaine');

// Retrieve all domains from user accounts
$domainsList = array();
foreach ($accounts as $cuid => $infos) {
    $mem->su($cuid);
    //Get all domain set to each user
    $domains = $dom->enum_domains();
    foreach ($domains as $domain) {
        $dom->lock();
        $domain_data = $dom->get_domain_all($domain);
        // Get all hosts (subdomain)
        $sub_domains = $domain_data['sub'];
        foreach ($sub_domains as $sub_domain) {
            $domainsList[] = $sub_domain['fqdn']; 
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
        vprint( _("\r$spacer\rRequesting domain %d/%d: %s"), array( $key + 1, count( $domainsList),$sub_domain )); 
        if( ! $certbot->isLocalAlterncDomain( $sub_domain ) ){
            continue;
        }   
        vprint( _(" hosted locally, running certbot..."), array( )); 
        
        $certbot->import($sub_domain);
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

