FROM debian:jessie

MAINTAINER Alireza Rahmani Khalili "alirezarahmani@live.com"

ENV TERM xterm

RUN apt-get update --fix-missing && apt-get install -y --force-yes curl sudo vim

RUN apt-get install -y --force-yes wget apt-transport-https lsb-release ca-certificates
RUN wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg

RUN echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list
RUN echo "deb http://ftp.uk.debian.org/debian jessie-backports main" >> /etc/apt/sources.list

RUN wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
RUN echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list

RUN curl --silent --location https://deb.nodesource.com/setup_4.x  | bash -
RUN DEBIAN_FRONTEND="noninteractive" apt-get update && apt-get install -y --force-yes \
    nginx \
    php7.2 \
    php7.2-cli \
    php7.2-fpm \
    php7.2-curl \
    php7.2-json \
    php7.2-mysql \
    php7.2-sybase \
    php7.2-opcache \
    php7.2-apcu \
    php7.2-xml \
    php7.2-redis \
    php7.2-intl \
    php7.2-mbstring \
    php7.2-zip \
    php7.2-soap \
    php7.2-xdebug \
    php-memcached \
    nodejs \
    git \
    unzip \
    openssh-server \
    p7zip \
    lzma \
    nano \

# configure php-fpm
RUN sed -i 's/^;*clear_env = .*/clear_env = no/' /etc/php/7.2/fpm/pool.d/www.conf

RUN curl -sS https://getcomposer.org/installer | php && \
    mv composer.phar /usr/local/bin/composer && chmod +x /usr/local/bin/composer

RUN mkdir /var/run/sshd
RUN echo 'root:root' | chpasswd
RUN sed -i 's/PermitRootLogin without-password/PermitRootLogin yes/' /etc/ssh/sshd_config
RUN sed 's@session\s*required\s*pam_loginuid.so@session optional pam_loginuid.so@g' -i /etc/pam.d/sshd
RUN echo "UseDNS no" >> /etc/ssh/sshd_config

RUN echo "KexAlgorithms diffie-hellman-group1-sha1" >> /etc/ssh/sshd_config

RUN echo "fastcgi_param PATH_TRANSLATED \$document_root\$fastcgi_script_name;" >> /etc/nginx/fastcgi_params

RUN mkdir /etc/nginx/ssl
RUN openssl ecparam -out /etc/nginx/ssl/nginx.key -name prime256v1 -genkey
RUN openssl req -new -batch -key /etc/nginx/ssl/nginx.key -out /etc/nginx/ssl/csr.pem
RUN openssl req -x509 -nodes -days 365 -key /etc/nginx/ssl/nginx.key -in /etc/nginx/ssl/csr.pem -out /etc/nginx/ssl/nginx.pem
RUN chmod 600 /etc/nginx/ssl/*

RUN ln -sf /dev/stdout /var/log/nginx/access.log
RUN ln -sf /dev/stderr /var/log/nginx/error.log

ADD docker-entrypoint.sh /usr/bin/docker-entrypoint
RUN chmod +x /usr/bin/docker-entrypoint
RUN sed -i 's/^user  nginx;/user  www-data;/' /etc/nginx/nginx.conf

RUN echo "apc.enable_cli=1" >> /etc/php/7.2/cli/php.ini
RUN echo "opcache.validate_timestamps=0" >> /etc/php/7.2/fpm/conf.d/10-opcache.ini
RUN echo "opcache.memory_consumption=128" >> /etc/php/7.2/fpm/conf.d/10-opcache.ini
RUN echo "opcache.opcache.max_accelerated_files=100000" >> /etc/php/7.2/fpm/conf.d/10-opcache.ini
RUN echo "apc.shm_size=128M" >> /etc/php/7.2/fpm/conf.d/20-apcu.ini
RUN sed -i "s/\(max_execution_time *= *\).*/\1180/" /etc/php/7.2/fpm/php.ini
RUN sed -i "s/\(upload_max_filesize *= *\).*/\1100M/" /etc/php/7.2/fpm/php.ini
RUN sed -i "s/\(post_max_size *= *\).*/\1100M/" /etc/php/7.2/fpm/php.ini
RUN sed -i "s/\(^.*max_input_vars *= *\).*/max_input_vars = 10000/" /etc/php/7.2/fpm/php.ini
RUN sed -i "s/\(pm.max_children = 5\).*/\pm.max_children = 50/" /etc/php/7.2/fpm/pool.d/www.conf
RUN sed -i "s/\(pm.max_spare_servers = 3\).*/\pm.max_spare_servers = 10/" /etc/php/7.2/fpm/pool.d/www.conf
RUN echo "xdebug.default_enable=0" >> /etc/php/7.2/fpm/conf.d/20-xdebug.ini
RUN echo "xdebug.profiler_output_dir=/var/www/cachegrind/" >> /etc/php/7.2/fpm/conf.d/20-xdebug.ini
RUN echo "xdebug.profiler_enable_trigger=1" >> /etc/php/7.2/fpm/conf.d/20-xdebug.ini
RUN echo "xdebug.profiler_output_name= cachegrind.out" >> /etc/php/7.2/fpm/conf.d/20-xdebug.ini

RUN mkdir /root/.ssh/
RUN ssh-keyscan github.com >> /root/.ssh/known_hosts
ADD memcached.ini /etc/php/7.2/fpm/conf.d/25-memcached.ini
ADD memcached.ini /etc/php/7.2/cli/conf.d/25-memcached.ini

ADD default.conf /etc/nginx/conf.d/default.conf

EXPOSE 22 443 80
WORKDIR /var/www/

ENTRYPOINT ["docker-entrypoint"]
CMD ["nginx", "-g", "daemon off;"]
