<?php

/*
  ----------------------------------------------------------------------
  AlternC - Web Hosting System
  Copyright (C) 2000-2018 by the AlternC Development Team.
  https://alternc.com/
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
  Purpose of file: Manage TLS Certificates for HTTPS Hosting
  through Letsencrypt and ACME v1 protocol, with HTTP validation.
  ----------------------------------------------------------------------
 */

// -----------------------------------------------------------------
/**
 * TLS Certificates management class
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
    /** Create a new TLS certificate for $fqdn, or renew any existing one.
     * @param  string $fqdn a fully qualified domain name (like sthg.example.com)
     * @return integer the ID of the newly created certificate in the table
     * or false if an error occurred
     */
    public function import($fqdn)
    {
        global $cuid, $msg, $ssl;
        $msg->log("certbot", "import","$fqdn");

        $output = array();
        $return_var = -1;
        exec("certbot --agree-tos --non-interactive --webroot -w /var/lib/letsencrypt/ certonly -d ".$fqdn." 2>&1", $output, $return_var);

        // Add certificate to panel
        if ($return_var == 0) {
            $key = file_get_contents('/etc/letsencrypt/live/'.$fqdn.'/privkey.pem');
            $crt = file_get_contents('/etc/letsencrypt/live/'.$fqdn.'/cert.pem');
            $chain = file_get_contents('/etc/letsencrypt/live/'.$fqdn.'/chain.pem');
            $msg->log("certbot", "import","new cert $fqdn OK");

            return $ssl->import_cert($key, $crt, $chain, "letsencrypt");
        }
        // Or log the error:
        $msg->log("certbot", "import","import failed, log is ".implode("\n        ",$output));
        return false;
    }

    // -----------------------------------------------------------------
    /**
    * Checks if dig returns our L_PUBLIC_IP
    */
    public function isLocalAlterncDomain($fqdn)
    {
        global $L_PUBLIC_IP, $L_OTHER_IPS;
        if ($L_OTHER_IPS != '') {
            $ips = "$L_PUBLIC_IP,$L_OTHER_IPS";
            $arr = explode(',', $ips);
        } else {
            $arr = array($L_PUBLIC_IP);
        }
        $out=array();
        exec("dig A +trace ".escapeshellarg($fqdn), $out);
        foreach ($out as $line) {
            foreach ($arr as $i) {
                if (preg_match('#.*IN.A.*?([0-9\.]*)$#', $line, $mat) && $mat[1] == $i) {
                    return true;
                }
             }
        }
        return false;
    }

}

/* Class m_certbot */
