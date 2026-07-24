---
title: Translations
description: Add UI and OpenAPI documentation translations to the Studio frontend from your bundle.
---

# Translations

Pimcore Studio uses the Symfony translation system with two custom domains for its UI and
API documentation. Bundles can contribute translation strings to both domains by placing YAML
files in a conventional directory — no extra configuration is needed.

| Domain | Purpose | Backed by Database |
|--------|---------|-------------------|
| `studio` | Studio React frontend (i18n) | Yes — `translations_studio` table |
| `studio_api_docs` | OpenAPI endpoint descriptions | No — file-based only |

---

## How Translations Are Loaded

When the Studio React frontend starts, it sends a `POST` request to `/pimcore-studio/api/translations`
to fetch all translation strings for the user's locale. The backend resolves translations
through the Symfony translator, which loads from both file-based YAML catalogues and the
`translations_studio` database table. The resulting catalogue is returned as a JSON object to
the frontend.

### Authenticated vs. Public Split

Not all translations are served to unauthenticated users. The controller checks the login
state and returns different sets:

| User State | Translations Returned |
|-----------|----------------------|
| **Authenticated** | Full `studio` catalogue for the requested locale |
| **Not authenticated** | Only keys listed in `PublicTranslations::PUBLIC_KEYS` (login form strings) |

This keeps the full catalogue private until the user has logged in.

---

## Auto-Creating Missing Keys

When the Studio frontend renders a translation key that does not yet exist in the `studio`
catalogue, it reports the key to the backend, which creates an empty entry in the
`translations_studio` database table. This mirrors the behaviour of the Classic UI and makes
newly introduced keys immediately visible in the Translations editor.

In setups where translations are provided exclusively through YAML files (for example
`translations/studio.en.yaml`), this auto-creation is often undesirable: non-translatable
labels such as numeric values or select-field choices end up polluting the `studio` domain
with keys that never need a translation.

Auto-creation can be disabled globally:

```yaml
# config/config.yaml
pimcore_studio_backend:
    translations:
        auto_create_missing_keys: false # default: true
```

When disabled, missing keys are no longer persisted automatically. Manually adding keys
through the Translations editor (and importing them) continues to work unchanged.

> **Note:** This setting only affects auto-creation triggered by the Studio frontend. Keys
> that the backend itself resolves through the Symfony translator may still be created by
> Pimcore core's write-on-miss behaviour for registered translation domains.

---

## OpenAPI Documentation Translations

Endpoint descriptions, summaries, and success response texts in OpenAPI attributes are
translation keys resolved from the `studio_api_docs` domain. This domain is **file-based
only** — it has no database table and no UI editor.

### File Placement

Place a `studio_api_docs.{locale}.yaml` file alongside your UI translation files:

```
vendor/my-vendor/my-bundle/
└── translations/
    ├── studio.en.yaml
    └── studio_api_docs.en.yaml
```

### Key Naming Convention

Keys follow the pattern used in controller OpenAPI attributes:

```yaml
# translations/studio_api_docs.en.yaml

# Tag description
bundle_tag_my_bundle_description: My Bundle endpoints

# Endpoint translations (operationId is the base)
bundle_my_bundle_config_get_description: |
  Returns the configuration for the given name.
bundle_my_bundle_config_get_summary: Get configuration by name
bundle_my_bundle_config_get_success_response: Configuration data
```

Each controller action typically needs three keys:

| Suffix | OpenAPI Attribute |
|--------|------------------|
| `_description` | `#[Get]` / `#[Post]` `description` parameter |
| `_summary` | `#[Get]` / `#[Post]` `summary` parameter |
| `_success_response` | `#[SuccessResponse]` `description` parameter |
