---
description:
---

# Settings Model

The Settings Model is a structural staple of most Craft plugins. It is generally not something you need to interact with directly.

If you want to manage the plugin's settings, go to **Settings > Mapbox** in the control panel. For more information, see the documentation regarding [the plugin settings page...](/getting-started/settings/)

:::warning FOR EDGE CASES ONLY
You will rarely need to call the Settings Model directly, it is for internal use only.

Read how to programmatically manage the [Mapbox Access Token...](/helper/api/)
:::

## Public Properties

### `accessToken`

_string_ - (Required) Your unique [Mapbox Access Token](/getting-started/access-token/) for this site.

### `enableJsLogging`

_bool_ - Whether to allow logging to the JavaScript console. Only relevant when displaying a dynamic map. Defaults to `true`.

### `minifyJsFiles`

_bool_ - Whether to use minified front-end JavaScript files. Only relevant when rendering a dynamic map. Defaults to `false`.

### `fieldParams`

_array_ - Additional optional parameters for configuring Address fields.

---
---

:::warning Additional Details
For more detailed information, check out the docs regarding the [PHP Config File...](/getting-started/config/)
:::
