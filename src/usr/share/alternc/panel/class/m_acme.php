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
class m_acme
{

    // -----------------------------------------------------------------
    /**
     * Constructor
     */
    public function m_acme()
    {
    }

    
    // -----------------------------------------------------------------
    /** Create a new TLS certificate for $fqdn, or renew any existing one.
     * @param  string $fqdn a fully qualified domain name (like sthg.example.com)
     * @return integer the ID of the newly created certificate in the table
     * or false if an error occurred
     */
    public function import($fqdn,$force = false)
    {
        global $cuid, $msg, $ssl;
        $msg->log("acme", "import","$fqdn");

        $output = array();
        $return_var = -1;
        $arg = "--agree-tos --non-interactive --webroot -w /var/lib/acme/";
        if ($force) {
            $arg.=" --force-renewal";
        }
        exec("certbot ".$arg." certonly -d ".$fqdn." 2>&1", $output, $return_var);

        // Add certificate to panel
        if ($return_var == 0) {
            $key = file_get_contents('/etc/letsencrypt/live/'.$fqdn.'/privkey.pem');
            $crt = file_get_contents('/etc/letsencrypt/live/'.$fqdn.'/cert.pem');
            $chain = file_get_contents('/etc/letsencrypt/live/'.$fqdn.'/chain.pem');
            $msg->log("acme", "import","new cert $fqdn OK");

            return $ssl->import_cert($key, $crt, $chain, "letsencrypt");
        }
        // Or log the error:
        $msg->log("acme", "import","import failed, log is ".implode("\n        ",$output));
        return false;
    }

    
    // -----------------------------------------------------------------
    /**
     * Prefer an acme test 
     * With dig +trace we can't follow cdn/proxy and some other use case
     * Workaround is to use --dry-run mode, we increase rate limit and could manage more certficate generation
     * https://letsencrypt.org/docs/staging-environment/
     */
    public function isLocalAlterncDomain($fqdn)
    {
        $output = array();
        $return_var = -1;
        exec("certbot --dry-run --non-interactive --webroot -w /var/lib/acme/ certonly -d ".$fqdn." 2>&1", $output, $return_var);

        // Dry run was successful
        if ($return_var == 0) {
                return true;
        }
        return false;
    }
}

/* Class m_acme */
