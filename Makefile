# DO NOT EDIT THIS FILE!
#
# It's auto-generated by sonata-project/dev-kit package.

all:
	@echo "Please choose a task."
.PHONY: all

lint: lint-composer lint-yaml lint-xml lint-xliff lint-php
.PHONY: lint

lint-composer:
	composer validate
.PHONY: lint-composer

lint-yaml:
	yamllint .

.PHONY: lint-yaml

lint-xml:
	find . -name '*.xml' \
		-not -path './vendor/*' \
		-not -path './src/Resources/public/vendor/*' \
		| while read xmlFile; \
	do \
		XMLLINT_INDENT='    ' xmllint --encode UTF-8 --format "$$xmlFile"|diff - "$$xmlFile"; \
		if [ $$? -ne 0 ] ;then exit 1; fi; \
	done

.PHONY: lint-xml

lint-xliff:
	find . -name '*.xliff' \
		-not -path './vendor/*' \
		-not -path './src/Resources/public/vendor/*' \
		| while read xmlFile; \
	do \
		XMLLINT_INDENT='  ' xmllint --encode UTF-8 --format "$$xmlFile"|diff - "$$xmlFile"; \
		if [ $$? -ne 0 ] ;then exit 1; fi; \
	done

.PHONY: lint-xliff

lint-php:
	php-cs-fixer fix --ansi --verbose --diff --dry-run
.PHONY: lint-php

cs-fix: cs-fix-php cs-fix-xml cs-fix-xliff
.PHONY: cs-fix

cs-fix-php:
	php-cs-fixer fix --verbose
.PHONY: cs-fix-php

cs-fix-xml:
	find . -name '*.xml' \
		-not -path './vendor/*' \
		-not -path './src/Resources/public/vendor/*' \
		| while read xmlFile; \
	do \
		XMLLINT_INDENT='    ' xmllint --encode UTF-8 --format "$$xmlFile" --output "$$xmlFile"; \
	done
.PHONY: cs-fix-xml

cs-fix-xliff:
	find . -name '*.xliff' \
		-not -path './vendor/*' \
		-not -path './src/Resources/public/vendor/*' \
		| while read xmlFile; \
	do \
		XMLLINT_INDENT='  ' xmllint --encode UTF-8 --format "$$xmlFile" --output "$$xmlFile"; \
	done
.PHONY: cs-fix-xliff

build:
	mkdir $@

test:
	vendor/bin/simple-phpunit -c phpunit.xml.dist
.PHONY: test

coverage:
	vendor/bin/simple-phpunit -c phpunit.xml.dist --coverage-clover build/logs/clover.xml
.PHONY: coverage
