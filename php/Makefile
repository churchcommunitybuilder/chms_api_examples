default:
	echo "Nothing to do"

install:
	bin/composer.phar install

update:
	git pull
	bin/composer.phar update

start:
	php -S localhost:8888 -t src/

test:
	./vendor/bin/phpunit tests
