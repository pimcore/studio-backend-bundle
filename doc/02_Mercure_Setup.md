# Mercure Setup

## Start and configure Mercure server

It is recommended to use an up-to-date instance of Mercure, e.g. via their docker container. For details see
[Mercure docs](https://mercure.rocks/docs/hub/install).

### Mercure configuration
Following Mercure configuration aspects need to be considered
- Add same JWT key to `MERCURE_PUBLISHER_JWT_KEY` and `MERCURE_SUBSCRIBER_JWT_KEY`
- JWT key needs to be minimum 256 bits (or 32 chars) long

## Example Configuration with docker compose
Following there are configuration snippets for a setup with docker compose.

In that case, Mercure is only exposed via nginx reverse proxy that runs under https. Internal communication is handled via
http (which is handy when using self-signed certificates in development context).

### nginx.conf
Configure reverse proxy in nginx to route Mercure traffic accordingly.

```conf
server {

    # (...) 
  
    location /hub {
        proxy_pass http://mercure/.well-known/mercure;
    }

    # Thumbnails
    # (...)

}
```

### docker-compose.yaml

Add Mercure to your docker compose file and make sure to add the configuration for JWT keys and anonymous directives.

```yaml 
  # add here all the other necessary containers... 

  # configure nginx to run under https
  nginx:
    image: nginx:stable-alpine
    ports:
      - 443:443
    depends_on:
      - php        
    volumes:
      - ./studio-demo:/var/www/html:ro
      - ./nginx.conf:/etc/nginx/conf.d/default.conf:ro
      # certificates for running nginx (for direct edit to work, system has to run with https)
      - ~/.certs:/etc/nginx/certs

  mercure:
    image: dunglas/mercure:latest
    restart: unless-stopped
    environment:
        # Disable HTTPS
        SERVER_NAME: ':80'
        MERCURE_PUBLISHER_JWT_KEY: '<your-256-bit-secret-min-32-chars>'
        MERCURE_SUBSCRIBER_JWT_KEY: '<your-256-bit-secret-min-32-chars>'
    expose:
        - "80"
    volumes:
        - pimcore-demo-mercure-data:/data
        - pimcore-demo-mercure-config:/config

  volumes:
      # ...
      # Add volumes for mercure
      pimcore-demo-mercure-data:
      pimcore-demo-mercure-config:
```

### config.yaml

Configure Pimcore application with JWT key as well as the client side and server side URLs for Mercure.

```yaml
pimcore_studio_backend:
    mercure_settings:
        jwt_key: '<your-256-bit-secret-min-32-chars>'
        hub_url_client: 'https://your-app-domain.com/hub'
        hub_url_server: 'http://mercure/.well-known/mercure'
```

## Check if Mercure is running
To see if Mercure is up and available, call ``https://your-app-domain.com/hub`` (or `client_side_url`
you configured in configuration). This request should return the text

```
Missing "topic" parameter.
```

> Also execute ``curl https://your-app-domain.com/hub`` (or `server_side_url` you configured in
> configuration) from the command line of the server to ensure that the URL is also accessible by the
> server itself.


## Additional Aspects


### Running Mercure with external URL

When running Mercure as external service with external URL, make sure to configure CSP properly.

#### Mercure Configuration

Sample based on docker compose, important part is the `cors_origins` directive.
```yaml
  mercure:
    image: dunglas/mercure
    restart: unless-stopped
    environment:
      # All other necessary configs ... 
      # ...
      
      # Add anonymous directives and cors configuration
      MERCURE_EXTRA_DIRECTIVES: |-
          cors_origins "https://<YOUR_PIMCORE_URL>"
```

### Webserver Reverse Proxy
When you are using Mercure default URLs, you might need to configure a reverse proxy to make sure the Mercure requests are
routed correctly to the Mercure instance.

Also, keep in mind, that communication needs to be HTTPS. Thus, it might be useful to place the Mercure server behind a reverse
proxy of the actual webserver (who handles the certificates).

Beware that the development ui is not working when using a reverse proxy. (See open ticket https://github.com/dunglas/mercure/issues/951)

#### Apache Reverse Proxy
For apache for example enable `http_proxy` in apache and add the following reverse proxy in your apache config:
```
   ProxyPass /hub/ http://localhost:3000/
   ProxyPassReverse /hub/ http://localhost:3000/
```

#### Nginx Reverse Proxy
For Nginx use following configuration:
```
	location /hub {
		proxy_pass http://mercure/.well-known/mercure;
	}
```

### Development UI
The development ui can be helpful to develop new features. It is possible to subscribe and publish messages.

With the configuration below, the development ui is available at `http://localhost:8080/.well-known/mercure/ui/`.

```yaml
  mercure:
    image: dunglas/mercure
    restart: unless-stopped
    environment:
      # All other necessary configs ... 
      # ...
      
      # Uncomment the following line to enable the development mode
      command: /usr/bin/caddy run --config /etc/caddy/dev.Caddyfile
      ports:
        - "8080:80"
```
The UI is available at `http://localhost:8080/.well-known/mercure/ui/` when running Mercure in development mode.

Please note the trailing slash in the URL, without it the UI will not be available.
