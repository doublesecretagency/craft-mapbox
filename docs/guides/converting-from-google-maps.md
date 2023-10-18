---
description: If migrating from the Google Maps plugin, it's very simple to import all of your existing Address data into the Mapbox plugin.
meta:
  - property: og:type
    content: website
  - property: og:url
    content: https://plugins.doublesecretagency.com/mapbox/guides/converting-from-google-maps/
  - property: og:title
    content: Converting from Google Maps | Mapbox plugin for Craft CMS
  - property: og:description
    content: If migrating from the Google Maps plugin, it's very simple to import all of your existing Address data into the Mapbox plugin.
  - property: og:image
    content: https://plugins.doublesecretagency.com/mapbox/images/guides/switch-field-type.png
  - property: twitter:card
    content: summary_large_image
  - property: twitter:url
    content: https://plugins.doublesecretagency.com/mapbox/guides/converting-from-google-maps/
  - property: twitter:title
    content: Converting from Google Maps | Mapbox plugin for Craft CMS
  - property: twitter:description
    content: If migrating from the Google Maps plugin, it's very simple to import all of your existing Address data into the Mapbox plugin.
  - property: twitter:image
    content: https://plugins.doublesecretagency.com/mapbox/images/guides/switch-field-type.png
---

# Converting from Google Maps

## Importing Address Data

Converting from an "Address (Google Maps)" field to an "Address (Mapbox)" field is relatively straightforward... simply **switch the field type** on the field's settings page.

<img class="dropshadow" :src="$withBase('/images/guides/switch-field-type.png')" alt="Screenshot of field type select being switched to Address (Mapbox)" width="210" style="margin-left:20px; margin-bottom:4px;">

Once you have saved the field as an "Address (Mapbox)" field, all data associated with that field will automatically be imported into the Mapbox plugin.

### One Field at a Time

When you update a single field, only the data for that specific field will be converted. Each Address field will need to be updated individually.

:::tip Field Configuration Not Included
The field's **settings** will not be ported, only the field's existing **data** will be transferred over.

You may still want to configure the Mapbox field to your liking, it will not automatically reflect how you had it configured with Google Maps.
:::

### Deploying to Production

When deploying to a production environment, you'll most likely be using [Project Config](https://craftcms.com/docs/4.x/project-config.html) to keep all of your configuration settings in sync. Don't worry, there will be little (or no) action required on your part.

All relevant Address data will be converted **once your Project Config changes are applied**.
