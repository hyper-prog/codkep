#
# Dockerfile of CodKep
# Automatic builded by hub.docker.com from
#  https://github.com/hyper-prog/codkep
#
#

FROM debian
LABEL maintainer="hyper80@gmail.com" Description="Codkep web framework on Debian linux with apache webserver"

RUN apt-get update && apt-get install -y apache2 libapache2-mod-php php-gd php-mysql php-pgsql php-apcu
COPY ./EXAMPLE/hub.docker.com/defaultdebian.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite \
    && rm -rf /var/www/html/* \
    && mkdir /var/www/html/sys \
    && ln -sf /proc/self/fd/1 /var/log/apache2/access.log \
    && ln -sf /proc/self/fd/1 /var/log/apache2/error.log
COPY . /var/www/html/sys/
RUN cd /var/www/html/sys \
    && rm -rf EXAMPLE \
    && cd /var/www/html \
    && ln -s sys/index.php index.php \
    && mkdir site \
    && mkdir data \
    && chown www-data:www-data data \
    && chmod 775 data
VOLUME ["/var/www/html/site","/var/www/html/data"]

EXPOSE 80

CMD ["/usr/sbin/apachectl", "-D", "FOREGROUND"]

