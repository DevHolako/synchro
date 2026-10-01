#=========================================================================#
# Synchro (HTTP): sends the domain to the app's Docker stack on           #
# 127.0.0.1:8000 (APP_PORT) instead of Apache. With "Force SSL" on, the   #
# include below redirects everything but Let's Encrypt checks to HTTPS.   #
# Based on Hestia's default.tpl; kept in the repo under docker/hestia/.   #
#=========================================================================#

server {
	listen      %ip%:%proxy_port%;
	server_name %domain_idn% %alias_idn%;
	error_log   /var/log/%web_system%/domains/%domain%.error.log error;
	access_log  /var/log/%web_system%/domains/%domain%.log combined;
	access_log  /var/log/%web_system%/domains/%domain%.bytes bytes;

	include %home%/%user%/conf/web/%domain%/nginx.forcessl.conf*;

	# Spreadsheet imports accept up to 5 MB
	client_max_body_size 16m;

	location ~ /\.(?!well-known\/|file) {
		deny all;
		return 404;
	}

	location / {
		proxy_pass         http://127.0.0.1:8000;
		proxy_http_version 1.1;
		proxy_set_header   Connection "";
		proxy_set_header   Host $host;
		proxy_set_header   X-Real-IP $remote_addr;
		proxy_set_header   X-Forwarded-For $proxy_add_x_forwarded_for;
		proxy_set_header   X-Forwarded-Proto $scheme;
		proxy_set_header   X-Forwarded-Host $host;
		proxy_set_header   X-Forwarded-Port $server_port;
		proxy_read_timeout 120s;
		proxy_send_timeout 120s;
	}

	location /error/ {
		alias %home%/%user%/web/%domain%/document_errors/;
	}

	include %home%/%user%/conf/web/%domain%/nginx.conf_*;
}
