FROM test/dockerhub/php:8.1.12-fpm-alpine3.16 as base

# set Moscow time zone
# we're working in Moscow time
RUN apk add --no-cache tzdata \
  && cp /usr/share/zoneinfo/Europe/Moscow /etc/localtime \
  && echo "Europe/Moscow" > /etc/timezone \
  && echo "date.timezone = Europe/Moscow" > /usr/local/etc/php/conf.d/timezone.ini

# install project libs dependencies
RUN apk add --no-cache \
    gettext \
    git \
    libpq-dev \
    rabbitmq-c-dev \
    librdkafka-dev

# intstall php-extensions \
RUN docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo pdo_pgsql pgsql

RUN docker-php-source extract \
    && git clone --branch v1.11.0 --depth 1 https://github.com/php-amqp/php-amqp.git /usr/src/php/ext/amqp \
    && cd /usr/src/php/ext/amqp && git submodule update --init \
    && docker-php-ext-install amqp

RUN apk --no-cache add pcre-dev ${PHPIZE_DEPS} \
    && pecl install redis rdkafka apcu \
    && docker-php-ext-enable redis rdkafka apcu \
    && apk del pcre-dev ${PHPIZE_DEPS}

# install php composer
RUN curl -sS https://getcomposer.org/installer | tee composer-setup.php \
    && php composer-setup.php --version=2.6.6 && rm composer-setup.php* \
    && chmod +x composer.phar && mv composer.phar /usr/bin/composer

# install grpc
RUN git clone --branch 2.2.5 --depth 1 https://github.com/mlocati/docker-php-extension-installer.git /usr/src/docker-php-extension-installer \
    && cd /usr/src/docker-php-extension-installer \
    && ./install-php-extensions grpc-1.64.1

# add webserver into image
# install nginx as part of application
RUN apk add --no-cache nginx curl ca-certificates \
    && ln -sf /dev/stdout /var/log/nginx/access.log \
    && ln -sf /dev/stderr /var/log/nginx/error.log \
    && mkdir -p /etc/nginx/conf.d

COPY docker/web/config/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/web/config/nginx/docker-entrypoint.d/ /docker-entrypoint.d/
COPY docker/web/config/nginx/docker-entrypoint-nginx.sh /docker-entrypoint-nginx.sh
RUN chmod -R +x /docker-entrypoint.d/*.sh && chmod -R 775 /docker-entrypoint-nginx.sh

# configure fpm pool
RUN rm /usr/local/etc/php-fpm.d/*
COPY docker/web/config/php-fpm/symfony.pool.conf /usr/local/etc/php-fpm.d/

WORKDIR /var/www

ENTRYPOINT [ "/docker-entrypoint-nginx.sh" ]

STOPSIGNAL SIGQUIT

EXPOSE 80
EXPOSE 9000

CMD ["nginx", "-g", "daemon off;"]

#
# deploy image for test/prod deployment
# here we have complete image with builded sourcecode
#
FROM base as deploy

ARG CI_LIBS_TOKEN
RUN echo "{" > /var/www/auth.json
RUN echo '"gitlab-token": {' >> /var/www/auth.json
RUN echo '"gitlab.test_corp": "'${CI_LIBS_TOKEN}'"'>> /var/www/auth.json
RUN echo "}" >> /var/www/auth.json
RUN echo "}" >> /var/www/auth.json

COPY docker/web/certs/* /usr/local/share/ca-certificates/
RUN update-ca-certificates

# install dependecies first for cache layers
COPY composer.* ./
RUN composer install --no-dev --no-scripts

# add source code
COPY . .
COPY docker/web/config/nginx/sites-enabled-templates/symfony.conf.template /etc/nginx/templates/symfony.conf.template

# finish composer with production mode
RUN composer install --no-dev --optimize-autoloader --classmap-authoritative


#
# dev image for local developer's setup
#
FROM base as dev

# should contains some debug tools
ARG PHP_XDEBUG_MODE
ARG PHP_XDEBUG_CLIENT_PORT
ARG PHP_XDEBUG_CLIENT_HOST

COPY docker/web/config/nginx/sites-enabled-templates/symfony.conf.template /etc/nginx/templates/symfony.conf.template
COPY docker/web/config/xdebug/xdebug.ini /tmp/xdebug.ini.template
RUN apk --no-cache add pcre-dev ${PHPIZE_DEPS} \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del pcre-dev ${PHPIZE_DEPS} \
    && \
      PHP_XDEBUG_MODE=${PHP_XDEBUG_MODE} \
      PHP_XDEBUG_CLIENT_PORT=${PHP_XDEBUG_CLIENT_PORT} \
      PHP_XDEBUG_CLIENT_HOST=${PHP_XDEBUG_CLIENT_HOST} \
      envsubst '$PHP_XDEBUG_MODE $PHP_XDEBUG_CLIENT_PORT $PHP_XDEBUG_CLIENT_HOST' < /tmp/xdebug.ini.template > /usr/local/etc/php/conf.d/xdebug.ini \
    && rm /tmp/xdebug.ini.template
