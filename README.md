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
* [apt-transport-https](https://packages.debian.org/search?keywords=apt-transport-https) package to use https bintray service.


# Installation

## Stable package

You can download last package from :
* github : [release page](../../releases/latest)
* bintray : [ ![Bintray](https://api.bintray.com/packages/alternc/stable/alternc-certbot/images/download.svg) ](https://bintray.com/alternc/stable/alternc-certbot/_latestVersion)
* from bintray repository
* on AlternC official repository at https://debian.alternc.org/

### On Wheezy

No more supported (last compatible version is 0.0.14)

### On Jessie

```shell
apt-get install apt-transport-https
echo "deb http://ftp.debian.org/debian jessie-backports main" >> /etc/apt/sources.list
echo "deb [trusted=yes] https://dl.bintray.com/alternc/stable stable main"  >> /etc/apt/sources.list.d/alternc.list
apt-get update
apt-get install -t jessie-backports certbot
apt-get install alternc-certificate-provider-letsencrypt
alternc.install
```

### On Stretch

```shell
apt-get install apt-transport-https
echo "deb [trusted=yes] https://dl.bintray.com/alternc/stable stable main"  >> /etc/apt/sources.list.d/alternc.list
apt-get update
apt-get install certbot alternc-certbot
alternc.install
```

## Nightly package

You can get last package from bintray, it's follow git master branch

```shell
echo "deb [trusted=yes] https://dl.bintray.com/alternc/nightly stable main"  >> /etc/apt/sources.list.d/alternc.list
apt-get update
apt-get upgrade
apt-get install alternc-certificate-provider-letsencrypt
alternc.install
```

# Configuration and Activation

Once alternc-certificate-provider-letsencrypt is installed, you must:
* run **alternc.install**

You can run also **/usr/lib/alternc/generate_certbot.php** to get faster certificate to all domains hosted.

# Packaging from source

To generate package we use either debuild on feature-package branch, or [fpm tool](https://github.com/jordansissel/fpm) on master:

```shell
apt-get install ruby ruby-dev rubygems build-essential
gem install --no-ri --no-rdoc fpm

git clone https://github.com/AlternC/alternc-certbot
cd alternc-certbot
make

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


