FROM prom/mysqld-exporter:latest

# Copy the MySQL configuration file to a temporary location
COPY my.cnf /tmp/my.cnf

# Move the file to the correct location as root
USER root
RUN mkdir -p /root && \
    mv /tmp/my.cnf /root/.my.cnf && \
    chmod 600 /root/.my.cnf

CMD [ "--web.listen-address=:9104" ] 