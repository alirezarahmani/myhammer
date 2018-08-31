FROM jamescarr/mcrouter

# Install packages
RUN DEBIAN_FRONTEND=noninteractive apt-get update
RUN DEBIAN_FRONTEND=noninteractive apt-get -y install memcached vim

ADD config.json /etc/mcrouter/config.json
ADD docker-entrypoint.sh /usr/bin/docker-entrypoint
RUN chmod +x /usr/bin/docker-entrypoint

# memcached public variable

EXPOSE 11211
EXPOSE 11212
EXPOSE 11213
EXPOSE 5000

ENTRYPOINT ["docker-entrypoint"]
CMD ["mcrouter", "-p", "5000", "-f", "/etc/mcrouter/config.json"]