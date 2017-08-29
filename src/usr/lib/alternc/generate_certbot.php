#!/usr/bin/php
<?php

// Ne v   rifie pas ma session :)
chdir("/usr/share/alternc/panel/");
require("/usr/share/alternc/panel/class/config_nochk.php");

// On passe super-admin
$admin->enabled=1;

// Get all alternc accounts
$accounts = $admin->get_list(1,0,FALSE,'domaine');

foreach ($accounts as $cuid => $infos) {
        $mem->su($cuid);

        //Get SSL set to each accounts
        $ssl_list = $ssl->get_list();

        $ssl_vhosts = array();
        foreach ($ssl_list as $ssl_item) {
                $ssl_vhosts[$ssl_item['fqdn']] = array(
                        'certid' => $ssl_item['id'],
                        'sslkey' => $ssl_item['sslkey']
                ) ;
        }

        //Get all domain set to each user
        $domains = $dom->enum_domains();
        foreach ($domains as $domain) {
                $dom->lock();
                $domain_data=$dom->get_domain_all($domain);
                // Get all hosts (subdomain) 
                $sub_domains=$domain_data['sub'];
                foreach($sub_domains as $sub_domain) {
                        $output = "";
                        $return_var = -1;
                        exec("certbot --agree-tos --non-interactive --apache certonly -d ".$sub_domain['fqdn']." 2>/dev/null",$output,$return_var);
                        //Add certificate to panel
                        if ($return_var == 0) {
                                $key = file_get_contents('/etc/letsencrypt/live/'.$sub_domain['fqdn'].'/privkey.pem');
                                $crt = file_get_contents('/etc/letsencrypt/live/'.$sub_domain['fqdn'].'/cert.pem');
                                $chain = file_get_contents('/etc/letsencrypt/live/'.$sub_domain['fqdn'].'/chain.pem');

                                if (
                                        !isset($ssl_vhosts[$sub_domain['fqdn']]) ||
                                        (
                                                isset($ssl_vhosts[$sub_domain['fqdn']]) &&
                                                $ssl_vhosts[$sub_domain['fqdn']]['sslkey'] != $key
                                        )
                                ) {
                                        $ssl->import_cert($key,$crt,$chain);
                                }
                        }
                }
                $dom->unlock();
        }
        $mem->unsu();
}