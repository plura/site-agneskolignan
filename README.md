# Agnes Kolignan – Site

WordPress site for [agneskolignan.com](https://agneskolignan.com).

## Structure

- `theme/` — Custom theme, deployed to `/wp-content/themes/agneskolignan/`
- `plugin/` — Custom plugin, deployed to `/wp-content/plugins/agneskolignan/`
- `other-plugins/` — Third-party plugins tracked in the repo

## Plugins

### Plura WP Plugin

This site uses the [Plura WP Plugin](https://github.com/plura/wp-plugin-plura/).

## Data Entry Instructions

### Collections

**Object Collection Info**
- **Client:** Indicate the clients associated with each collection.
  Example: For "Spring Summer 2025", include "Fabrique" and "Goelia".
  Note: If the client is not added here, the collection will not appear on that client's page.

### Objects

Each object has the following fields:

- **Active:** Indicates whether the object is active in the database. If not active, the object will not be loaded into the frontend.
- **Client:** The client to whom this object is linked.
- **Type:** The category of the object (e.g., "Print", "Accessory", "Jewelry").
- **Year:** The year of creation or release of the object.
- **Materials:** List of materials used in the object.
- **Dimensions:** Physical dimensions of the piece.
- **Gallery Credits:** Optional photographer or contributor credits for the gallery images.
- **Description:** A short descriptive text about the object.
- **Gallery:** A gallery of high-quality images showcasing the object.
- **Collections:** Indicate which collection(s) the object belongs to.
- **Tags:** Add descriptive tags for categorization and filtering. Examples: "Floral", "Womenswear", "Minimal", "Gold".

## Development Notes

### Collections URL routing

The plugin adds rewrite rules that support the following URL structures:

- `/collections/{collection}/` — all objects in a collection
- `/collections/{collection}/{client}/` — objects in a collection filtered by client (used when a collection spans multiple clients)

These are parsed during `init` into query vars `ak_object_collection` and `ak_object_collection_client`, accessible via `get_query_var()`.

## Reference

- https://fullsiteediting.com/lessons/creating-block-templates-for-custom-post-types/
- https://imranhsayed.medium.com/adding-rewrite-rules-in-wordpress-tutorial-b8603a37dcab
- https://stackoverflow.com/a/33411986
