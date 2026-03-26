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
