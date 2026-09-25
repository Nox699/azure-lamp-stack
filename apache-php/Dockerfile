FROM php:8.3-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql     && a2enmod headers rewrite     && printf '%s\n'        'expose_php=Off'        > /usr/local/etc/php/conf.d/hardening.ini     && printf '%s\n'        'ServerTokens Prod'        'ServerSignature Off'        'TraceEnable Off'        > /etc/apache2/conf-available/hardening.conf     && printf '%s\n'        '<IfModule mod_headers.c>'        '  Header always set X-Content-Type-Options "nosniff"'        '  Header always set X-Frame-Options "DENY"'        '  Header always set Referrer-Policy "strict-origin-when-cross-origin"'        '  Header always set Content-Security-Policy "default-src '\''self'\''; style-src '\''self'\'' '\''unsafe-inline'\''; form-action '\''self'\''; frame-ancestors '\''none'\''"'        '</IfModule>'        > /etc/apache2/conf-available/security-headers.conf     && a2enconf hardening security-headers

WORKDIR /var/www/html
