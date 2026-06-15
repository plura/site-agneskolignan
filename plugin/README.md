## Collections

This section covers how the plugin handles collections and their URL routing.

### Supported URL Structures

- `/collections/{collection}/`  
  Shows all objects in a given collection.

- `/collections/{collection}/{client}/`  
  Used only when a collection is associated with multiple clients.  
  Filters the objects in the collection to show only those related to the specified client.

### Purpose

Some collections involve contributions or ownership from multiple clients. These rewrite rules make it possible to filter the collection view by client via the URL.

### Implementation

Rewrite rules are added during the `init` hook:

- Two-segment URLs are parsed into:
  - `ak_object_collection`
  - `ak_object_collection_client` (optional, only for multi-client collections)

These values are accessible via `get_query_var()` for querying or displaying context-sensitive data.
