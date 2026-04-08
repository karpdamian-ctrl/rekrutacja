#!/bin/bash

set -e

composer update

php -S 0.0.0.0:8000 -t public public/index.php