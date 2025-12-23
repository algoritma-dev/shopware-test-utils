#!/bin/bash

## ATTENTION ##
# Shopware Commercial is under license and requires a valid license to use.
# Make sure you have the right to use this package before installing it.
echo "Installing private package..."

# install
composer require store.shopware.com/swagcommercial --prefer-stable --ignore-platform-reqs

# Rollback composer.json
git checkout composer.json

echo "Done!"