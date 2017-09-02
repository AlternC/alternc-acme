# Purpose

It's a alternc plugin to help to manage certbot certificates.

It's enable and generate a certbot certificat to panel alternc.

# Requirement

You need :
* debian server (wheezy or jessie)
* alternc >= 3.2
* certbot package
 * with wheezy : [from antonbatenev backport](https://software.opensuse.org//download.html?project=home%3Aantonbatenev%3Aletsencrypt&package=certbot)
 * with jessie : [from backport](https://packages.debian.org/jessie-backports/certbot)
* [apt-transport-https](https://packages.debian.org/search?keywords=apt-transport-https) package if you use bintray service


# Installation

## Stable version

You can download last package from :
* github : [release page](../../releases/latest)
* bintray : [ ![Bintray](https://api.bintray.com/packages/alternc/stable/alternc-certbot/images/download.svg) ](https://bintray.com/alternc/stable/alternc-certbot/_latestVersion)
* from bintray repository

### Wheezy


```shell
apt-get install apt-transport-https
echo "deb [trusted=yes] https://dl.bintray.com/alternc/stable stable main"  >> /etc/apt/sources.list.d/alternc.list
echo 'deb http://download.opensuse.org/repositories/home:/antonbatenev:/letsencrypt/Debian_7.0/ /' > /etc/apt/sources.list.d/certbot.list
apt-get update
apt-get install certbot
apt-get install alternc-certbot
alternc.install
```


### Jessie

```shell
apt-get install apt-transport-https
echo "deb http://ftp.debian.org/debian jessie-backports main" >> /etc/apt/sources.list
echo "deb [trusted=yes] https://dl.bintray.com/alternc/stable stable main"  >> /etc/apt/sources.list.d/alternc.list
apt-get update
apt-get install -t jessie-backports certbot
apt-get install alternc-certbot
alternc.install
```

## Nightly version

You can get last package from bintray, it's follow master branch

```shell
echo "deb [trusted=yes] https://dl.bintray.com/alternc/nightly stable main"  >> /etc/apt/sources.list.d/alternc.list
apt-get update
apt-get upgrade
apt-get install alternc-certbot
alternc.install
```

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
* [ ] Auto renew all domains
