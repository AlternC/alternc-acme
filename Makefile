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

package:
	fpm -s dir -t deb \
		-n $(NAME) \
		-v $(VERSION) \
		--conflicts "alternc-certbot" \
		`if [ "$(ITERATION)" ]; then echo "--iteration $(ITERATION)"; fi` \
		-m alternc@webelys.com \
		--license GPLv3 \
		--category admin \
		--architecture all \
		--depends "apt-utils, debconf, alternc (>= 3.5.0~rc1), alternc-ssl, certbot, certbot" \
		--after-install "debian/postinst" \
		--after-remove  "debian/postrm" \
		--chdir src \
		.
install:
	cp -r src/* $(DESTDIR)/

