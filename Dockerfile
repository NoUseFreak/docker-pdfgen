FROM nousefreak/php7

MAINTAINER Dries De Peuter <dries@nousefreak.be>

RUN sed -i 's|root /var/www/html;|root /var/www/html/web;|' /etc/nginx/sites-enabled/default

RUN apt-get install -y \
    fontconfig \
    xfonts-75dpi \
    libxrender1 \
    xfonts-base

RUN wget http://download.gna.org/wkhtmltopdf/0.12/0.12.2.1/wkhtmltox-0.12.2.1_linux-trusty-amd64.deb \
    && dpkg -i wkhtmltox-0.12.2.1_linux-trusty-amd64.deb

COPY . /var/www/html

RUN php -r "readfile('https://getcomposer.org/installer');" | php \
	&& php composer.phar install \
	&& rm composer.phar
