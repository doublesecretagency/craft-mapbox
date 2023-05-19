<img align="left" src="https://plugins.doublesecretagency.com/mapbox/images/icon.svg" alt="Plugin icon">

# Mapbox plugin for Craft CMS

**Maps in minutes. Powered by the Mapbox API.**

---

### Includes easy-to-use Address Fields

When managing your Craft data, each location can be set with a convenient Address field.

### Create flexible Dynamic Maps

Add markers, use popups, style maps, change marker icons, and much more.

The plugin features a powerful [universal API](https://plugins.doublesecretagency.com/mapbox/dynamic-maps/universal-api/) which works nearly identically across **JavaScript, Twig, and PHP!**

```twig
{# Get the entries #}
{% set entries = craft.entries.section('locations').all() %}

{# Place them on a dynamic map #}
{{ mapbox.map(entries).tag() }}
```

For more information, see the [full dynamic maps docs...](https://plugins.doublesecretagency.com/mapbox/dynamic-maps/)

---

## How to Install the Plugin

### Installation via Plugin Store

See the complete instructions for [installing via the plugin store...](https://plugins.doublesecretagency.com/mapbox/getting-started/#installation-via-plugin-store)

### Installation via Console Commands

To install the **Mapbox** plugin via the console, follow these steps:

```sh
# Go to your Craft project
cd /path/to/project

# Tell Composer to load the plugin
composer require doublesecretagency/craft-mapbox

# Tell Craft to install the plugin
./craft plugin/install mapbox
```

>Alternatively, you can visit the **Settings > Plugins** page to finish the installation. If installed via the control panel, you will automatically be redirected to configure the plugin after installation is complete.

### Access Token

Once the plugin is installed, you will need to [add a Mapbox Access Token...](https://plugins.doublesecretagency.com/mapbox/getting-started/access-token/)

---

## Further Reading

If you haven't already, flip through the [complete plugin documentation](https://plugins.doublesecretagency.com/mapbox/). The examples above are just the tip of the iceberg, there is so much more that is possible!

And if you have any remaining questions, feel free to [reach out to us](https://www.doublesecretagency.com/contact) (via Discord is preferred).

**On behalf of Double Secret Agency, thanks for checking out our plugin!** 🍺

<p align="center">
    <img width="130" src="https://www.doublesecretagency.com/resources/images/dsa-transparent.png" alt="Logo for Double Secret Agency">
</p>
