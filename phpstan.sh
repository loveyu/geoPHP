#!/usr/bin/env bash
exec sudo docker run --rm -v "$(pwd)":/app ghcr.io/phpstan/phpstan:latest-php8.3 analyse --configuration=/app/phpstan.neon
