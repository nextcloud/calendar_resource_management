# SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later

app_name=calendar_resource_management

project_dir=$(CURDIR)
build_dir=$(CURDIR)/build/artifacts
appstore_dir=$(build_dir)/$(app_name)
package_name=$(build_dir)/$(app_name).tar.gz

# Tools (override on the command line if needed, e.g. `make composer-install COMPOSER=composer2`)
COMPOSER=composer
NPM=npm

.PHONY: all
all: build

# Install all dependencies and build the production frontend assets.
.PHONY: build
build: composer-install build-js-production

# PHP runtime dependencies only (no dev tooling ends up in the package).
.PHONY: composer-install
composer-install:
	$(COMPOSER) install --no-dev --prefer-dist --optimize-autoloader

# Compile the frontend bundle into js/.
.PHONY: build-js-production
build-js-production:
	$(NPM) ci
	$(NPM) run build --if-present

# Remove all build output.
.PHONY: clean
clean:
	rm -rf $(build_dir)

# Assemble a clean copy of the app and package it for the Nextcloud App Store.
# NOTE: this only packages what is already present in the working tree. Run
# `make build` (or the CI build steps) first so js/ and vendor/ are populated.
.PHONY: appstore
appstore: clean
	mkdir -p $(appstore_dir)
	rsync -a \
	  --exclude='/build' \
	  --exclude='/.git' \
	  --exclude='/.github' \
	  --exclude='/.git-blame-ignore-revs' \
	  --exclude='/.gitignore' \
	  --exclude='/node_modules' \
	  --exclude='/src' \
	  --exclude='/tests' \
	  --exclude='*.cache' \
	  --exclude='*.log' \
	  --exclude='*.log.*' \
	  --exclude='/renovate.json' \
	  --exclude='/REUSE.toml' \
	  --exclude='/eslint.config.mjs' \
	  --exclude='/stylelint.config.js' \
	  --exclude='/vite.config.mjs' \
	  --exclude='/tsconfig.json' \
	  --exclude='/phpstan.neon' \
	  --exclude='/.php-cs-fixer.dist.php' \
	  --exclude='/.php-cs-fixer.cache' \
	  --exclude='/package.json' \
	  --exclude='/package-lock.json' \
	  --exclude='/composer.json' \
	  --exclude='/composer.lock' \
	  --exclude='/vendor/bin' \
	  --exclude='/vendor-bin' \
	  --exclude='/Makefile' \
	  $(project_dir)/ $(appstore_dir)/
	tar -czf $(package_name) -C $(build_dir) $(app_name)
