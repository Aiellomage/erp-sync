# CI image for erp-sync: PHP, the extensions the project needs and Composer.
# The code is not copied here: the runner clones it in every job.

FROM php:8.5.12-cli-trixie@sha256:7e06a1ead42d3225c83569c2049fd343dc0587df09e8656dad551ecc115d046c

LABEL authors="antonio"

# git and unzip are used by Composer to download packages
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/*

# PHP extension installer, copied from its image (one file, no new stage)
COPY --from=mlocati/php-extension-installer:2.13.2 /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions amqp pdo_mysql intl zip

# Composer, copied from the official image
COPY --from=composer:2.11.1 /usr/bin/composer /usr/local/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1
