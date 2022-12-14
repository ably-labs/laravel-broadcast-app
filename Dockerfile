FROM alpine:latest

ENV \
  APP_DIR="/app" \
  APP_PORT="80"

COPY . /app

RUN apk add --update \
    curl \
    php \
    php-bcmath \
    php-curl \
    php-ctype \
    php-dom \
    php-fileinfo \
    php-mbstring \
    php-opcache \
    php-openssl \
    php-pdo \
    php-pdo_sqlite \
    php-phar \
    php-session \
    php-tokenizer \
    php-xml \
    php-xmlwriter \
    php-zip \
	nodejs \
	npm \
    && rm -rf /var/cache/apk/*

RUN curl -sS https://getcomposer.org/installer | php -- \
  --install-dir=/usr/bin --filename=composer

WORKDIR $APP_DIR

RUN composer install
RUN npm install

RUN php artisan migrate:fresh --seed

CMD php artisan serve --host=0.0.0.0 --port=$APP_PORT
