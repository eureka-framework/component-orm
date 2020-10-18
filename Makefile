.PHONY: install update phpcs phpcbf tests

PHP_FILES := $(shell find src tests -type f -name '*.php')

install:
	composer install

update:
	composer update

composer.lock: composer.json
	composer install

vendor/bin/phpunit: composer.lock

build/reports/cs/eureka.xml: composer.lock $(PHP_FILES)
	mkdir -p build/reports/cs
	./vendor/bin/phpcs --standard=./ci/phpcs/eureka.xml --cache=./build/cs_eureka.cache -p --report-full --report-checkstyle=./build/reports/cs/eureka.xml src/ tests/

build/reports/php80/compatibility_check.xml: composer.lock $(PHP_FILES)
	mkdir -p build/reports/php80
	./vendor/bin/phpcs --standard=./ci/phpcs/PHP80Compatibility.xml --cache=./build/php80.cache -p --report-full --report-checkstyle=./build/reports/php80/compatibility_check.xml src/ tests/

phpcs: build/reports/cs/eureka.xml

php80compatibility: build/reports/php80/compatibility_check.xml

phpcbf: composer.lock
	./vendor/bin/phpcbf --standard=./ci/phpcs/eureka.xml src/ tests/

build/reports/phpunit/unit.xml build/reports/phpunit/unit.cov: vendor/bin/phpunit $(PHP_FILES)
	mkdir -p build/reports/phpunit
	php -dzend_extension=xdebug.so ./vendor/bin/phpunit -c ./phpunit.xml.dist --coverage-clover=./build/reports/phpunit/clover.xml --log-junit=./build/reports/phpunit/unit.xml --coverage-php=./build/reports/phpunit/unit.cov --coverage-html=./build/reports/coverage/ --fail-on-warning

tests: build/reports/phpunit/unit.xml build/reports/phpunit/unit.cov

testdox: vendor/bin/phpunit $(PHP_FILES)
	php -dzend_extension=xdebug.so ./vendor/bin/phpunit -c ./phpunit.xml.dist --fail-on-warning --testdox
