FROM debian

LABEL maintainer="hyper80@gmail.com" Description="This image of apache and CodKep"

RUN apt-get update && apt-get install -y apache2 libapache2-mod-php7.0 php-gd php-mysql php-apcu git
RUN a2enmod rewrite
COPY default.conf /etc/apache2/sites-available/000-default.conf
RUN ln -sf /proc/self/fd/1 /var/log/apache2/access.log &&  ln -sf /proc/self/fd/1 /var/log/apache2/error.log
RUN cd /var/www/html && git clone https://github.com/hyper-prog/codkep.git sys && ln -s sys/index.php index.php && mkdir site
EXPOSE 80
VOLUME ["/var/www/html/site","/var/www/html/data"]

CMD ["/usr/sbin/apachectl", "-D", "FOREGROUND"]