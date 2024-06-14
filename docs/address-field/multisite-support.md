---
description: For projects with multiple sites, each site can store a different Address field value.
---

# Multisite Support

For projects with multiple sites, each site can store a different Address field value.

All native translation methods are supported:

<img class="dropshadow" :src="$withBase('/images/address-field/translatable.png')" alt="Screenshot of Address field translation options" width="345" style="margin-bottom:20px">

---
---

### Multisite Migration

When updating the plugin to **v1.2** (Craft 4) or **v2.1** (Craft 5), a significant migration will be run in the database. If your project contains multiple sites, all existing rows in the `mapbox_addresses` table will be duplicated for each site.

Effectively, the number of existing table rows will be <span style="text-decoration:underline">multiplied</span> by the number of sites in your project.

This allows Craft to treat each Address as unique within each site.
