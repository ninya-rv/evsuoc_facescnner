FROM php:8.2-cli

RUN docker-php-ext-install mysqli

WORKDIR /app

COPY . /app

CMD php -S 0.0.0.0:$PORT