# Mercure Setup

## Start and Configure Mercure

It is recommended to use an up-to-date instance of Mercure, e.g. via their docker container. For details see
[Mercure docs](https://mercure.rocks/docs/hub/install).

### Configuration
Following Mercure configuration aspects need to be considered
- Add same JWT key to `MERCURE_PUBLISHER_JWT_KEY` and `MERCURE_SUBSCRIBER_JWT_KEY`
- Activate `anonymous` to allow Pimcore Studio UI to connect to Mercure

### Configure Mercure URLs
URLs for accessing Mercure server-side (for updating state information within application
services) and client-side (for getting updates in Pimcore Studio UI) need to be configured via symfony configuration
tree as follows:

```yaml
pimcore_studio_backend:
    mercure_settings:
        # URL of mercure accessible for client.
        hub_url_client: 'http://mercure/.well-known/mercure'
        # URL of mercure accessible for server.
        hub_url_server: 'http://mercure/.well-known/mercure'
```
You need to configure the full URL including protocol, port and path to Mercure here.

## Example Configuration with docker compose
Following there are configuration snippets for a setup with docker compose.

In that case, Mercure is only exposed via nginx reverse proxy that runs under https. Internal communication runs via
http (which is handy when using self-signed certificates in development context).

### docker-compose.yaml

Add mercure to your docker compose file and make sure to add the configuration for JWT keys and anonymous directives.

```yaml 
  # add here all the other necessary containers... 

  # configure ngnix to run under https
  nginx:
    image: nginx:stable-alpine
    ports:
      - 443:443
    depends_on:
      - php-fpm         
    volumes:
      - ./demo-px-enterprise:/var/www/html:ro
      
      # mount ngnix configuration to be adapted (see below) 
      - ./nginx.conf:/etc/nginx/conf.d/default.conf:ro
      
      # certificates for running ngnix (for direct edit to work, system has to run with https)
      - ~/.certs:/etc/nginx/certs

  mercure.local:
    image: dunglas/mercure
    restart: unless-stopped
    environment:
      # Disable HTTPS
      SERVER_NAME: ':80'
      
      # Add JWT keys configured
      MERCURE_PUBLISHER_JWT_KEY: '<YOUR_JWT_KEY>'
      MERCURE_SUBSCRIBER_JWT_KEY: '<YOUR_JWT_KEY>'     
      
      # Add anomyous directives 
      MERCURE_EXTRA_DIRECTIVES: anonymous      

```

### nginx.conf

Configure reverse proxy in ngnix to route mercure traffic accordingly.

```conf
server {

    # (...) 
  
  	location /hub {
		proxy_pass http://mercure.local/.well-known;
	}

    # Thumbnails
    # (...)

}
```

### config.yaml

Configure Pimcore application with JWT key as well as the client side and server side URLs for mercure.

```yaml
pimcore_studio_backend:
    mercure_settings:
        jwt_key: 'YOUR_JTW_KEY_WHICH_NEEDS_TO_BE_MIN_256_BITS_LONG'
        hub_url_client: 'http://mercure/.well-known/mercure'
        hub_url_server: 'http://mercure/.well-known/mercure'
```

## Check if Mercure is running
To see if Mercure is up and available, call ``https://your-app-domain.com/hub/.well-known/mercure`` (or `client_side_url`
you configured in configuration). This request should return the text

```
Missing "topic" parameter.
```

> Also execute ``curl https://your-app-domain.com/hub/.well-known/mercure`` (or `server_side_url` you configured in
> configuration) from the command line of the server to ensure that the URL is also accessible by the
> server itself.


## Additional Aspects


### Running Mercure with external URL

When running Mercure as external service with external URL, make sure to configure CSP properly.

#### Mercure Configuration

Sample based on docker compose, important part is the `cors_origins` directive.
```yaml
  mercure.local:
    image: dunglas/mercure
    restart: unless-stopped
    environment:
      # All other necessary configs ... 
      # ...
      
      # Add anonymous directives and cors configuration
      MERCURE_EXTRA_DIRECTIVES: |-
          cors_origins "https://<YOUR_PIMCORE_URL>"
          anonymous
```

### Webserver Reverse Proxy
When you are using Mercure default URLs, you might need to configure a reverse proxy to make sure the Mercure requests are
routed correctly to the Mercure instance.

Also, keep in mind, that communication needs to be HTTPS. Thus, it might be useful to place the Mercure server behind a reverse
proxy of the actual webserver (who handles the certificates).

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
		proxy_pass http://localhost:3000/.well-known;
	}
```
