NAME=alternc-certificate-provider-letsencrypt
VERSION=$(shell git tag -l --points-at HEAD)
ITERATION=""

ifeq ($(strip $(VERSION)),)
	VERSION=$(shell git describe --tags --abbrev=0)
	ifeq ($(strip $(VERSION)),)
		VERSION="0.0.0"
	endif
	ITERATION=$(shell date +'%y%m%d%H%M%S')
endif

.PHONY: clean package

all: clean package

clean:
	rm -f $(NAME)_*.deb

translate:
	po2debconf -o debian/templates debian/templates

package:
	fpm -s dir -t deb \
		-n $(NAME) \
		-v $(VERSION) \
		--provides "alternc-certbot" \
		--conflicts "alternc-certbot" \
		`if [ "$(ITERATION)" ]; then echo "--iteration $(ITERATION)"; fi` \
		-m alternc@webelys.com \
		--license GPLv3 \
		--category admin \
		--architecture all \
		--depends "apt-utils, debconf, alternc (>> 3.5.0), alternc-ssl, certbot, certbot" \
		--deb-config "debian/config" \
		--after-install "debian/postinst" \
		--after-remove  "debian/postrm" \
		--chdir src \
		.