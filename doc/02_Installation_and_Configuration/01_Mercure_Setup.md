---
title: Mercure Setup
description: Configure Mercure for real-time updates in Pimcore Studio.
---

# Mercure Setup

## Start and configure Mercure server

Use Mercure hub **0.24**, e.g. via their docker container (`dunglas/mercure:v0.24`). For details see
[Mercure docs](https://mercure.rocks/docs/hub/install).

> Always pin the image to `v0.24` instead of using `latest` or an untagged image. Mercure 1.0 and later contain breaking
> changes (token format, subscription parameters, hub configuration, development UI), so an unpinned image will pull a
> hub that this setup is not written for.

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
    image: dunglas/mercure:v0.24
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

If you would like to retrieve the URLs from within the application, you can use the following methods:
- `getClientSideUrl()` to get the client URL
- `getServerSideUrl()` to get the server URL

These methods are available in the `Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UrlServiceInterface` class interface.

## Check if Mercure is running
To see if Mercure is up and available, call ``https://your-app-domain.com/hub`` (or `client_side_url`
you configured in configuration). This request should return the text

```
Missing "topic" parameter.
```

or

```
Unauthorized
```

> Also execute ``curl https://your-app-domain.com/hub`` (or `server_side_url` you configured in
> configuration) from the command line of the server to ensure that the URL is also accessible by the
> server itself.


## Additional Aspects

### Multiple application nodes
When Pimcore runs on several application nodes (load balancer, cluster), run **one** Mercure hub (one logical hub that all nodes
share; for a highly available hub see below) for all of them. Do not start one independent hub per node behind a load
balancer.

Mercure stores subscriptions and the update history in the hub itself. The free transports (`bolt`, `local`) are
single-node, and a load balancer alone does not synchronize hubs. With one hub per node, an update published by node 1
never reaches a browser that is connected to the hub of node 2. See the
[Mercure cluster docs](https://mercure.rocks/docs/hub/cluster).

Recommended setup:
- Run Mercure as a separate service (not co-located with a single application node) and mount a persistent `/data` volume.
- Use the same `jwt_key` on all application nodes, and set both `MERCURE_PUBLISHER_JWT_KEY` and
  `MERCURE_SUBSCRIBER_JWT_KEY` on the hub to that same `jwt_key` value.
- Point `hub_url_server` of every node to that hub, and route `hub_url_client` (public URL) to it.

One hub is usually enough, as Mercure is lightweight. It only carries live updates. If it is unreachable, publishing
errors are logged. If it hangs or is overloaded, publishing requests can block until the HTTP timeout (up to 60 seconds)
before failing. Only job progress falls back to polling; other live updates (e.g. the live notification toast and badge,
or Direct Edit events) are not delivered while the hub is down. Notifications themselves are stored before they are
published, so they still show up after the next reload.

If the hub itself needs to be highly available, Mercure offers clustered editions (Mercure Enterprise with a shared
transport, or Mercure Cloud; both are licensed by Mercure, not by Pimcore). Note that these are Mercure 1.x hubs, while
this page is written for hub 0.24 (see the warning at the top), and their compatibility with this bundle's setup has not
been verified. Alternatively, restart the single hub quickly with a process supervisor or your orchestrator.

> The legacy Direct Edit path (`FileEditControllerTrait` of `pimcore/direct-edit`, deprecated since 2026.3, removal
> planned for 2027.1) asks the hub which clients are subscribed (subscriptions API). This requires that all clients
> are connected to the one hub the application asks, with the `subscriptions` directive enabled. If you run that hub as
> a cluster, only the Redis/Valkey transport supports subscriptions cluster-wide.
>
> Direct Edit also has its own, deprecated hub settings (`mercure_settings.server_side_url`, `parameters.mercure.hub.*`)
> that can override the hub URL and key. Remove them or set them to the same values as the hub configured above.


### Running Mercure with external URL

When running Mercure as external service with external URL, make sure to configure CSP properly.

#### Mercure Configuration

Sample based on docker compose, important part is the `cors_origins` directive.
```yaml
  mercure:
    image: dunglas/mercure:v0.24
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

Beware that the development UI is not working when using a reverse proxy. (See open ticket https://github.com/dunglas/mercure/issues/951)

#### Apache Reverse Proxy
For Apache, for example, enable `http_proxy` in apache and add the following reverse proxy in your apache config:
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
The development UI can be helpful to develop new features. It is possible to subscribe and publish messages.

With the configuration below, the development ui is available at `http://localhost:8080/.well-known/mercure/ui/`.

```yaml
  mercure:
    image: dunglas/mercure:v0.24
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
