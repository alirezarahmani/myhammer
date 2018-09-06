FROM percona

MAINTAINER Alireza Rahmani Khalili "alirezarahmani@live.com"

ENV TERM xterm

ADD my.cnf /etc/mysql/my.cnf
ADD query.sql /docker-entrypoint-initdb.d
