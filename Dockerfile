FROM php:7.2-fpm-stretch

ENV CODE_PATH /var/www
ENV PHP_ENV_FILE .env.develop

RUN echo 'deb http://mirrors.aliyun.com/debian/ stretch main non-free contrib\n\
deb-src http://mirrors.aliyun.com/debian/ stretch main non-free contrib\n\
deb http://mirrors.aliyun.com/debian-security stretch/updates main\n\
deb-src http://mirrors.aliyun.com/debian-security stretch/updates main\n\
deb http://mirrors.aliyun.com/debian/ stretch-updates main non-free contrib\n\
deb-src http://mirrors.aliyun.com/debian/ stretch-updates main non-free contrib\n\
deb http://mirrors.aliyun.com/debian/ stretch-backports main non-free contrib\n\
deb-src http://mirrors.aliyun.com/debian/ stretch-backports main non-free contrib\n\
' > /etc/apt/sources.list \
    && apt-get update \
    && apt-get install -y \
    nginx \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libmcrypt-dev \
    libpng-dev \
    openssl \
    libssh-dev \
    libnghttp2-dev \
    libhiredis-dev \
    zip unzip \
    supervisor \
#    curl \
    nscd 
#    && rm -rf /var/lib/apt/lists/* \
RUN pecl install -o -f redis \
    # install php pdo_mysql opcache
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
#    && docker-php-ext-configure opcache --enable-opcache \
    && docker-php-ext-install -j$(nproc) gd pdo_mysql mysqli opcache zip bcmath \
    && docker-php-ext-enable redis

# config
RUN /bin/cp /usr/share/zoneinfo/Asia/Shanghai /etc/localtime \
    && echo 'Asia/Shanghai' > /etc/timezone

WORKDIR /var/www
#COPY docker/composer-setup.php /var/www
# install composer
#RUN  php composer-setup.php \
#    && php -r "unlink('composer-setup.php');" \
#    && mv composer.phar /usr/local/bin/composer \
#    && composer config -g repo.packagist composer https://mirrors.aliyun.com/composer/

EXPOSE 80
