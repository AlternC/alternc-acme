# Purpose

This AlternC plugin get certificates with Letsencrypt service. It generates:
* a panel certificate (as apache.pem)
* any certificate to each domains hosted

When a domain is added, the plugin try to get a new certificate.
We check that the DNS is answering with our PUBLIC_IP before asking Letsencrypt

# Requirements

You'll need :
* a Debian server (from Jessie)
* AlternC >= 3.5
* The Certbot package
 * with Jessie : [from backports](https://packages.debian.org/jessie-backports/certbot)
 * with Stretch : [from stable](https://packages.debian.org/stretch/certbot) or [from backports](https://packages.debian.org/stretch-backports/certbot) for wildcards


# Installation

## Stable package

You can download last package from :
* github : [release page](../../releases/latest)
* on AlternC official repository at https://debian.alternc.org/

### On Wheezy

No more supported (last compatible version is 0.0.14)

### On Jessie or Stretch

Go to https://github.com/AlternC/alternc-certbot/releases and download last *.deb release.

```shell
apt-get update
apt-get install -t jessie-backports certbot
dpkg -i alternc-certbot*.deb
alternc.install
```

## Nightly package

We no more propose nightly package. You must package it yourself

# Configuration and Activation

Once alternc-certificate-provider-letsencrypt is installed, you must:
* run **alternc.install**

You can run also **/usr/lib/alternc/generate_certbot.php** to get faster certificate to all domains hosted.

# Packaging from source

To generate package we use either debuild / dpkg-buildpackage

```shell
apt-get build-essential
git clone https://github.com/AlternC/alternc-certbot
dpkg-buildpackage -us -uc -b
```


# ROADMAP

* [x] Auto renew panel certificat (0.0.2)
* [x] Auto detect new domain add from panel (0.0.3)
* [x] Don't stop apache to get certificate (0.0.4)
* [x] Auto renew all domains (0.0.5)
* [x] Provide a stretch compatibility (0.0.6)
* [x] Correct update cron (0.0.11)
* [x] Prevent https redirection before certificate generation (0.0.12)
* [x] More verbose on alternc.install process as certificates generation can took some times (0.0.14)
* [x] Stop old debian support
* [x] Renaming project to follow AlternC recommandation (since 3.5.x)
* [x] Change packaging system, move to debuild solution (0.0.15)
* [ ] push into official AlternC repository


