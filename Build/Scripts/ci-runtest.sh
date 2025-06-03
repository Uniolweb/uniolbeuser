#!/bin/bash

PHPVERSION="8.2"
# we do not need to set the platform version if correct PHP major.minor version is required in composer.json
#PHP_PLATFORM_VERSION="8.2.15"
#PHP_PLATFORM_VERSION=""

# abort on error
set -e
# show commands before executing
#set -x

composer validate

echo "create link to auth.json"
rm -f auth.json
ln -s /var/www/site-uol11/auth.json auth.json

# --------------------
# pre composer install
# --------------------
# preliminary checks before composer install

Build/Scripts/check_composer_lock_does_not_exist.sh
Build/Scripts/check_no_platform.php_exists_in_composer.json.sh
# Must do composer validate before setting platform
composer validate

# ----------------
# composer install
# ----------------

# check if PHP_PLATFORM_VERSION is empty or undefined
if [[ "${PHP_PLATFORM_VERSION}x" != "x" ]];then
    echo "add config platform.php $PHP_PLATFORM_VERSION to composer.json "
    composer config platform.php "$PHP_PLATFORM_VERSION"
else
    echo "Variable PHP_PLATFORM_VERSION is not set, do not add platform to composer.json "
fi

echo "composer install"
# todo use runTests.sh -s composerUpdate in the future
composer install

echo "remove platform in composer.json"
composer config --unset platform.php
composer config --unset platform

# --------------
# perform checks
# --------------

echo "cgl"
Build/Scripts/runTests.sh -s cgl -n  -p ${PHPVERSION}

echo "lint"
Build/Scripts/runTests.sh -s lint  -p ${PHPVERSION}

echo "phpstan"
Build/Scripts/runTests.sh -s phpstan -p ${PHPVERSION}

echo "Unit tests"
Build/Scripts/runTests.sh -s unit -v  -p ${PHPVERSION}

#echo "functional tests"
#Build/Scripts/runTests.sh -d mariadb -s functional  -p ${PHPVERSION}

# -------
# cleanup
# -------

echo "cleanup"
rm -f .php-cs-fixer.cache

echo "done"
