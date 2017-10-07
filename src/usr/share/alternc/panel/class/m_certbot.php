<?php

/*
  ----------------------------------------------------------------------
  AlternC - Web Hosting System
  Copyright (C) 2000-2017 by the AlternC Development Team.
  https://alternc.org/
  ----------------------------------------------------------------------
  LICENSE

  This program is free software; you can redistribute it and/or
  modify it under the terms of the GNU General Public License (GPL)
  as published by the Free Software Foundation; either version 2
  of the License, or (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  To read the license please visit http://www.gnu.org/copyleft/gpl.html
  ----------------------------------------------------------------------
  Purpose of file: Manage SSL Certificates and HTTPS Hosting
  ----------------------------------------------------------------------
 */

// -----------------------------------------------------------------
/**
 * SSL Certificates management class
 */
class m_certbot
{

    // -----------------------------------------------------------------
    /**
     * Constructor
     */
    public function m_certbot()
    {
    }

    // -----------------------------------------------------------------
    /** Import an existing ssl Key, Certificate and (maybe) a Chained Cert
     * @param  string    $fqdn a full fqdn
     * @return int|false the ID of the newly created certificate in the table
     * or false if an error occurred
     */
    public function import($fqdn)
    {
        global $cuid, $msg, $ssl;
        $msg->log("certbot", "import");

        //Get SSL set to this account
        $ssl_list = $ssl->get_list();

        $ssl_vhosts = array();
        foreach ($ssl_list as $ssl_item) {
            $ssl_vhosts[$ssl_item['fqdn']] = array(
                        'certid' => $ssl_item['id'],
                        'sslkey' => $ssl_item['sslkey']
                ) ;
        }

        $output = "";
        $return_var = -1;
        exec("certbot --agree-tos --non-interactive --webroot -w /var/lib/letsencrypt/ certonly -d ".$fqdn." 2>/dev/null", $output, $return_var);

        //Add certificate to panel
        if ($return_var == 0) {
            $key = file_get_contents('/etc/letsencrypt/live/'.$fqdn.'/privkey.pem');
            $crt = file_get_contents('/etc/letsencrypt/live/'.$fqdn.'/cert.pem');
            $chain = file_get_contents('/etc/letsencrypt/live/'.$fqdn.'/chain.pem');

            if (
                        !isset($ssl_vhosts[$fqdn]) ||
                        (
                                isset($ssl_vhosts[$fqdn]) &&
                                $ssl_vhosts[$fqdn]['sslkey'] != $key
                        )
                ) {
                return $ssl->import_cert($key, $crt, $chain);
            }
        }
        return false;
    }
}

/* Class m_certbot */
