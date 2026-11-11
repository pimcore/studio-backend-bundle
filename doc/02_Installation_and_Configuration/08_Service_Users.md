---
title: Service Users
description: Machine identities for integrations, agents and pipelines, managed through the Studio API.
---

# Service Users

A service user is a machine identity for an integration, an agent, an MCP client or a pipeline. It is a Pimcore
user of type `serviceuser` (`Pimcore\Model\User\ServiceUser`) and is authorized like a user: permissions, roles,
workspaces, class and document type restrictions and MCP server Access apply to it. It differs from a human user:

- It is never an administrator, has no usable password (the core stores an unusable random value) and no two-factor
  authentication.
- It cannot log in to Studio, and password recovery does not reach it.
- It is not part of the user tree or the user collection. Names are unique across users and service users.

## Managing service users

The endpoints require the `users` permission and are documented in the OpenAPI specification under
"Service User Management".

| Method and path | Purpose |
|---|---|
| `GET /pimcore-studio/api/service-users` | List service users |
| `GET /pimcore-studio/api/service-user/{id}` | Get one service user |
| `POST /pimcore-studio/api/service-user` | Create a service user and its OAuth client |
| `POST /pimcore-studio/api/service-user/from-user/{id}` | Create a service user from a user |
| `PUT /pimcore-studio/api/service-user/{id}` | Update contact email, permissions, roles, restrictions and workspaces |
| `POST /pimcore-studio/api/service-user/{id}/deactivate` | Deactivate a service user |
| `DELETE /pimcore-studio/api/service-user/{id}` | Delete a service user |
| `POST /pimcore-studio/api/service-user/{id}/rotate-secret` | Rotate the client secret |
| `POST /pimcore-studio/api/service-user/{id}/mcp-token` | Issue an MCP access token |
| `DELETE /pimcore-studio/api/service-user/{id}/mcp-token` | Revoke all MCP access tokens |

Creating a service user also registers its confidential OAuth client (see
[Service-user clients](./06_OAuth_Server.md#service-user-clients-client-credentials)). The response contains the
client id and the client secret. **The secret is shown only in that response**; only its hash is stored. Rotate the
secret to get a new one; the previous secret stops working at once. Access tokens already issued with the old secret
stay valid until they expire, at most `client_credentials.access_token_ttl` seconds.

Creating a service user from a user copies the user's permissions, roles, class and document type restrictions,
translation language restrictions and workspaces. The user is not changed and admin rights are never copied. Only
an administrator may create a service user from an administrator.

The update endpoint refuses `name`, `password`, `admin` and two-factor fields with a validation error. The name
stays fixed because MCP server access is granted by name.

## MCP access tokens

Until OAuth Client Credentials is enabled (`pimcore_studio_backend.oauth.client_credentials.enabled`), a service
user authenticates at the MCP endpoints with an MCP access token. Static personal access tokens
(`pimcore_studio_backend.mcp.authentication.tokens`) are deprecated and are not used for service users.

A token lives 1 to 90 days (default 30). It is shown once; only its hash is stored. Issue one through the API or on
the command line:

```bash
# issue a token valid for 30 days for service user 42
bin/console studio-backend-bundle:service-user:mcp-token 42 --ttl-days=30

# revoke every MCP access token of service user 42
bin/console studio-backend-bundle:service-user:mcp-token 42 --revoke
```

Store the token in a secret store and send it as `Authorization: Bearer <token>`. To let the service user use an
MCP server, grant its name Access in the server's sharing settings.

## Which credential to use

Use the OAuth client (client id and secret, `client_credentials` grant) for pipelines and integrations that can
perform the grant, and only once `pimcore_studio_backend.oauth.client_credentials.enabled` is on. Use an MCP access
token for everything else, such as MCP clients that take a bearer token. The service-user schema reports which one
works: `clientCredentialsEnabled` is `false` while the grant is off (the client secret then authenticates nothing),
and `mcpTokens` summarises the tokens as `active` (tokens that have not expired) and `latestExpiresAt` (the latest
expiry among them, `null` without one). Neither the tokens nor their hashes are ever returned.

While the grant is off, the `clientSecret` in the create and rotate responses is `null`, so no unusable secret reaches
the browser. The OAuth client is still registered on create, and rotating the secret while the grant is off still
invalidates the old secret, but the new one is withheld; turn the grant on and rotate again to get a usable secret.

## Deactivation and deletion

- Deactivating a service user stops new client-credentials tokens from being issued, and refuses its OAuth and MCP
  access tokens from the next request on.
- Deleting a service user deletes its OAuth client, revokes its OAuth tokens (by `jti`) and MCP access tokens,
  and removes its entries from the access lists of all MCP servers stored in a writeable location. This happens
  whichever way the service user is deleted, so a later user or service user with the same name inherits nothing.
  An MCP server shipped in read-only configuration cannot be changed; a warning is logged, and the entry has to be
  removed from that configuration by hand.
