---
description: The plugin's settings can also be managed via a PHP config file.
---

# PHP Config File

The config settings available on the plugin's [Settings page](/getting-started/settings/) can also be managed via PHP in a config file. By setting these values in `config/mapbox.php`, they take precedence over whatever may be set via the control panel.

```shell
# Copy this file...
/vendor/doublesecretagency/craft-mapbox/src/config.php

# To here... (and rename it)
/config/mapbox.php
```

Much like the `db.php` and `general.php` files, `mapbox.php` is [environmentally aware](https://craftcms.com/docs/4.x/config/#multi-environment-configs). You can also pass in environment values using the `getenv` PHP method.

```php
return [

    // Mapbox Access Token (required)
    'accessToken' => getenv('MAPBOX_ACCESSTOKEN'),

    // Whether to log JS progress to the console (when a map is rendered)
    'enableJsLogging' => true,

];
```

## Settings available via Control Panel

It's also possible to set the Mapbox Access Token via the control panel. For more information, consult the documentation regarding the [Settings](/getting-started/settings/) page.

You may also want to learn more about managing your [Mapbox Access Token](/getting-started/access-token/).

## Settings available only via PHP file

This setting can only be managed via the PHP config file.

### `enableJsLogging`

_bool_ - Defaults to `true`.

Whether to [log dynamic map progress](/dynamic-maps/troubleshooting/) to the JavaScript console when the site is in `devMode`.

```php
// Prevent dynamic maps from logging to the console
'enableJsLogging' => false
```
