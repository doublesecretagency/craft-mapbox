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

    // Additional optional parameters for configuring Address fields
    'fieldParams' => [
        'limit' => 6,       // Number of results to return (max 10)
        'language' => 'en', // https://en.wikipedia.org/wiki/IETF_language_tag
        'country' => null,  // https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2
    ]

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

### `fieldParams`

_array_ - Defaults to the following array:

```php
'fieldParams' => [
    'limit' => 6,
    'language' => 'en',
    'country' => null,
]
```

| Parameter  | Description                                                                                                                            |
|------------|----------------------------------------------------------------------------------------------------------------------------------------|
| `limit`    | The maximum number of search results to display at once. Cannot be set higher than 10.                                                 |
| `language` | The [IETF language tag](https://en.wikipedia.org/wiki/IETF_language_tag). If null, `en` will be used.                                  |
| `country`  | An [ISO 3166 alpha-2 country code](https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2). If null, results will not be filtered by country. |

For example, you could set the language of the Address field (ie: `ja` for Japanese).

Setting a `country` will restrict all search results to be within that country (ie: `JP` for Japan only).

```php
// Set the language and country filter for all Address fields
'fieldParams' => [
    'language' => 'ja',
    'country' => 'JP',
]
```

:::warning Defaults to user's language
If not specified in the config file, the language of each Address field will default to match the language of the logged-in user.
:::

:::tip Only applies to Address fields in the Control Panel
If you need to change the language of your _front-end_ maps, see the guide for [Changing the Map Language](/guides/changing-map-language/).
:::
