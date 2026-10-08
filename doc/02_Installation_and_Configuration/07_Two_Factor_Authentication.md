# Two-Factor Authentication

Studio users can protect their login with a second factor: a time-based code from an authenticator app
(Google Authenticator, Microsoft Authenticator, Aegis or any other TOTP app). Studio configures
[scheb/2fa](https://github.com/scheb/2fa) itself, so no further bundle is needed.

> **Note:** this is Studio's own two-factor authentication. It is built for installations without the Classic admin
> UI (`pimcore/admin-ui-classic-bundle`). With the Classic admin UI installed, its configuration would override
> Studio's (issuer, server name and the rule for who needs a code); that combination is not supported.

## Who needs a code

Two settings on each user decide it, both editable in the user management:

| `required` | `enabled` | Login |
|---|---|---|
| no | no | Password only |
| any | yes | Password, then a code from the authenticator app |
| yes | no | Password, then the user sets up an authenticator app; the first code from it finishes the login |

Until a required user has set up two-factor authentication, whoever logs in with that user's password first sets
it up. Make sure the password reaches the right person.

The login with a login token (`POST /login/token`, e.g. from a password reset mail or a login link created in the
user management) does not ask for a code, as in the Classic admin UI.

## Configuration

```yaml
pimcore_studio_backend:
    two_factor_authentication:
        # Name shown in the authenticator app. Default: Pimcore
        issuer: 'Acme PIM'
        # Shown next to the user name in the authenticator app.
        # Default: the router's request context host (%router.request_context.host%)
        server_name: 'pim.acme.com'
```

The default server name is the host the router is configured with, not the host of the current request. Without
`framework.router.default_uri` it is `localhost`. Set `default_uri` or `server_name` so users can tell their
entries apart in the app.

Codes sent to `POST /login/2fa` are limited per user: after 5 attempts within 5 minutes, further codes are refused
with `429` until the window is over, even a correct one. A correct code resets the count, a password login does not.
The limiter is `studio_two_factor_code`; adjust it under `framework.rate_limiter` (see
[Rate Limiting](./04_Rate_Limiting.md)).

## Login flow

1. `POST /login` with user name and password. Without two-factor authentication the answer is an empty `200` and the
   user is logged in, as before. Otherwise the answer is `200` with
   `{"twoFactorRequired": true, "twoFactorStep": "verify"}` or `"setup"`, and no user data. The login is not finished:
   until it is, every endpoint that needs a login answers `401`, except the setup endpoint in step `setup`.
2. Step `verify`: `POST /login/2fa` with `{"code": "123456"}`. A wrong code answers `401` and can be retried.
3. Step `setup`: `POST /user/two-factor/setup` returns `{secret, otpauthUri}`; show `otpauthUri` as a QR code and
   `secret` for manual entry. Then send the first code from the app to `POST /login/2fa`; it enables two-factor
   authentication and finishes the login.
4. To go back, `POST /logout`.

The setup endpoint is opened for this step by Studio itself; the `access_control` rules from the
[installation](./README.md) stay as they are.

## Profile and user management

| Endpoint | Who | What |
|---|---|---|
| `POST /user/two-factor/setup` | the current user | New secret, pending until confirmed. During a renewal the current secret keeps working until then. |
| `POST /user/two-factor/confirm` | the current user | `{"code"}` from the new secret enables it; a wrong code answers `422`, no pending setup `409`. |
| `DELETE /user/two-factor` | the current user | Disables two-factor authentication; refused (`403`) while it is required. |
| `DELETE /user/{id}/two-factor` | user management permission | Resets a user's two-factor authentication. Admins may reset any user, other users only themselves, also while it is required for them. `required` stays as it is, so a required user sets it up again at the next login. |

The secret is only ever returned by the setup endpoint.
