<img align="left" src="https://plugins.doublesecretagency.com/mapbox/images/icon.svg" alt="Plugin icon">

# Mapbox plugin for Craft CMS

**Maps in minutes. Powered by the Mapbox API.**

---

### Create flexible [Dynamic Maps](https://plugins.doublesecretagency.com/mapbox/dynamic-maps/)

Add markers, use popups, change marker icons, style maps, and much, much more...

<p align="center">
    <img width="850" src="https://raw.githubusercontent.com/doublesecretagency/craft-mapbox/8e6170c2fcf2794c563bf3db43ed7f8c4e488aa5/docs/.vuepress/public/images/maps/example.png" alt="Screenshot of a dynamic map">
</p>

### [Universal API](https://plugins.doublesecretagency.com/mapbox/dynamic-maps/universal-api/) works across JavaScript, Twig, and PHP

The plugin features a powerful universal API which is nearly identical across multiple programming languages...

```js
// JavaScript
const map = mapbox.map()
    .markers(locations, options)
    .style(mapStyle)
    .center(coords)
    .zoom(level);
```

```twig
{# Twig #}
{% set map = mapbox.map()
    .markers(locations, options)
    .style(mapStyle)
    .center(coords)
    .zoom(level) %}
```

```php
// PHP
$map = Mapbox::map()
    ->markers($locations, $options)
    ->style($mapStyle)
    ->center($coords)
    ->zoom($level);
```

### Includes an easy-to-use [Address Field](https://plugins.doublesecretagency.com/mapbox/address-field/)

When managing your Craft data, each location can be set with a convenient Address field...

<p align="center">
    <img width="640" src="https://raw.githubusercontent.com/doublesecretagency/craft-mapbox/v1-dev/docs/.vuepress/public/images/address-field/annotated.png" alt="Annotated screenshot of an Address field">
</p>

---

## How to Install the Plugin

To get started, see the [**complete installation instructions ➡️**](https://plugins.doublesecretagency.com/mapbox/getting-started/)

Once the plugin is installed, a [Mapbox Access Token](https://plugins.doublesecretagency.com/mapbox/getting-started/access-token/) will also be required.

---

## Further Reading

If you haven't already, flip through the [complete plugin documentation](https://plugins.doublesecretagency.com/mapbox/). The examples above are just the tip of the iceberg, there is so much more that is possible!

And if you have any remaining questions, feel free to [reach out to us](https://www.doublesecretagency.com/contact) (via Discord is preferred).

**On behalf of Double Secret Agency, thanks for checking out our plugin!** 🍺

<p align="center">
    <img width="130" src="https://www.doublesecretagency.com/resources/images/dsa-transparent.png" alt="Logo for Double Secret Agency">
</p>
