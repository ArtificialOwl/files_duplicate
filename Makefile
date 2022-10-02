app_name=FilesDuplicate

build_dir=$(CURDIR)/build/artifacts
sign_dir=$(build_dir)/sign
package_name=$(shell echo $(app_name) | tr '[:upper:]' '[:lower:]')
version=0.1.0

all: release

appstore: release

cs-check: composer-dev
	composer cs:check

cs-fix: composer-dev
	composer cs:fix

clean:
	rm -rf $(build_dir)

# composer packages
composer:
	composer install --prefer-dist --no-dev
	composer upgrade --prefer-dist --no-dev

composer-dev:
	composer install --prefer-dist --dev
	composer upgrade --prefer-dist --dev

release: clean composer
	mkdir -p $(sign_dir)
	rsync -a \
	--exclude=/build \
	--exclude=/docs \
	--exclude=/translationfiles \
	--exclude=/.tx \
	--exclude=/tests \
	--exclude=.git \
	--exclude=/.github \
	--exclude=/l10n/l10n.pl \
	--exclude=/CONTRIBUTING.md \
	--exclude=/issue_template.md \
	--exclude=/README.md \
	--exclude=/composer.json \
	--exclude=/testConfiguration.json \
	--exclude=node_modules \
	--exclude=/composer.lock \
	--exclude=/.gitattributes \
	--exclude=/.gitignore \
	--exclude=/.scrutinizer.yml \
	--exclude=/.travis.yml \
	--exclude=/Makefile \
	./ $(sign_dir)/$(package_name)
	tar -czf $(build_dir)/$(package_name)-$(version).tar.gz \
		-C $(sign_dir) $(package_name)
