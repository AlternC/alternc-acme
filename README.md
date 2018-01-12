# Purpose

This alternc plugin get any certificate with let's encrypt service. It genereate :
* a panel certificate (use with apache.pem)
* any certificate to each domains hosted

When a domain is added, plugin try to get new certificate. As dns service can be in late, we can have a delay before to see any new certificate.

# Requirement

You need :
* debian server (from wheezy to Stretch)
* alternc >= 3.2
* certbot package
 * with wheezy : [from antonbatenev backport](https://software.opensuse.org//download.html?project=home%3Aantonbatenev%3Aletsencrypt&package=certbot)
 * with jessie : [from backport](https://packages.debian.org/jessie-backports/certbot)
 * with stretch : [from stable](https://packages.debian.org/stretch/certbot)
* [apt-transport-https](https://packages.debian.org/search?keywords=apt-transport-https) package to use https bintray service.


# Installation

## Stable package

You can download last package from :
* github : [release page](../../releases/latest)
* bintray : [ ![Bintray](https://api.bintray.com/packages/alternc/stable/alternc-certbot/images/download.svg) ](https://bintray.com/alternc/stable/alternc-certbot/_latestVersion)
* from bintray repository

### With Wheezy

```shell
apt-get install apt-transport-https
echo "deb [trusted=yes] https://dl.bintray.com/alternc/stable stable main"  >> /etc/apt/sources.list.d/alternc.list
echo 'deb http://download.opensuse.org/repositories/home:/antonbatenev:/letsencrypt/Debian_7.0/ /' > /etc/apt/sources.list.d/certbot.list
apt-get update
apt-get install certbot
apt-get install alternc-certbot
alternc.install
```
### With Jessie

```shell
apt-get install apt-transport-https
echo "deb http://ftp.debian.org/debian jessie-backports main" >> /etc/apt/sources.list
echo "deb [trusted=yes] https://dl.bintray.com/alternc/stable stable main"  >> /etc/apt/sources.list.d/alternc.list
apt-get update
apt-get install -t jessie-backports certbot
apt-get install alternc-certbot
alternc.install
```

### With Stretch

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
apt-get install alternc-certbot
alternc.install
```

# Configuration and Activation

Once alternc-certbot installed , you must :
* run **alternc.install**

You can run also **/usr/lib/alternc/generate_certbot.php** to get faster certificate to all domains hosted.

# Packaging from source

To generate package we use [fpm tool](https://github.com/jordansissel/fpm)

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
