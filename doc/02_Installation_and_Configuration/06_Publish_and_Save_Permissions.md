---
title: Publish and Save Permissions
description: How the publish, unpublish and save workspace permissions interact for data objects.
---

# Publish and Save Permissions

The workspace permissions `save`, `publish` and `unpublish` are configured separately per user and workspace.
For data objects, however, the **publish permission includes saving**: a user who may publish an object may also
persist the changes sent together with the publish request, without additionally holding `save`.

## Data Objects

| Request                                                        | Required workspace permission |
|----------------------------------------------------------------|-------------------------------|
| `PUT /data-objects/{id}` with `task: publish`                  | `publish`                     |
| `PUT /data-objects/{id}` with `task: unpublish`                | `unpublish`                   |
| `PUT /data-objects/{id}` with `task: save`, `autoSave`, `version` or without a task | `save`   |

Publishing an object saves it as published. The data that is part of the same request, for example
`editableData`, is saved together with the publication. The Studio Backend therefore does
not check the `save` permission additionally for a `publish` or `unpublish` task; the `publish` or `unpublish`
permission is checked instead.

Without a `publish` or `unpublish` task, writing data object data always requires the `save` permission. The `PATCH`
endpoints follow the same rule for data: patching `editableData` of a data object requires `save`, also when a
publish task is sent along with it.

## Assets

Assets have no `save` permission. The `publish` permission is their edit permission.

## Practical Consequences

- Grant `publish` (and `unpublish`) only to users who are also trusted to change the content of the data objects in
  the workspace; they can change data by publishing it.
- A user with `save` but without `publish` can store and version changes, but cannot publish them.
