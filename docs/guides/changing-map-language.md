---
description: When creating a map, you can set which language will be used for most labels.
meta:
  - property: og:type
    content: website
  - property: og:url
    content: https://plugins.doublesecretagency.com/mapbox/guides/changing-map-language/
  - property: og:title
    content: Changing the Map Language | Mapbox plugin for Craft CMS
  - property: og:description
    content: When creating a map, you can set which language will be used for most labels.
  - property: og:image
    content: https://plugins.doublesecretagency.com/mapbox/images/maps/japanese.png
  - property: twitter:card
    content: summary_large_image
  - property: twitter:url
    content: https://plugins.doublesecretagency.com/mapbox/guides/changing-map-language/
  - property: twitter:title
    content: Changing the Map Language | Mapbox plugin for Craft CMS
  - property: twitter:description
    content: When creating a map, you can set which language will be used for most labels.
  - property: twitter:image
    content: https://plugins.doublesecretagency.com/mapbox/images/maps/japanese.png
---

# Changing the Map Language

When [creating a map](/dynamic-maps/basic-map-management/#map-locations-options), it's possible to control the displayed language. You can set this via the `mapOptions`, specifically by using the `language` option.

:::code
```js
// Set language to Japanese
const options = {
    'mapOptions': {
        'language': 'ja'
    }
};

// Create the map
const map = mapbox.map(locations, options);
```
```twig
{# Set language to Japanese #}
{% set options = {
    'mapOptions': {
        'language': 'ja'
    }
} %}

{# Create the map #}
{% set map = mapbox.map(locations, options) %}
```
```php
// Set language to Japanese
$options = [
    'mapOptions' => [
        'language' => 'ja'
    ]
];

// Create the map
$map = Mapbox::map($locations, $options);
```
:::

With that in place, most text on the map will appear in your specified language...

<p align="center">
    <img width="704" class="dropshadow" :src="$withBase('/images/maps/japanese.png')" alt="Example of map in Japanese">
</p>

:::warning Language Code Format
According to the [Mapbox docs](https://docs.mapbox.com/mapbox-gl-js/api/map/#map-parameters), the `language` must be a [BCP-47 language code](https://en.wikipedia.org/wiki/IETF_language_tag#List_of_common_primary_language_subtags).
:::
