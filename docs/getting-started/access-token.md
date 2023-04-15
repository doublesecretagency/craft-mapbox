---
description: Learn how to create a Mapbox Access Token and add them to your Craft CMS website. This detailed guide shows how it all ties together.
---

# Mapbox Access Token

In order to use the Mapbox API, you will need an **Access Token**. Follow these authoritative instructions for getting a Mapbox Access Token...

:::tip Official Mapbox Links
 - [About Access Tokens](https://docs.mapbox.com/help/glossary/access-token/)
 - [Manage Access Tokens](https://account.mapbox.com/access-tokens/)
:::

## Creating an Access Token

:::warning Mapbox Web Services APIs
The Mapbox API actually consists of a collection of multiple [Web Services APIs](https://docs.mapbox.com/api/overview/).
:::

### Token Scopes

When creating a new token, you can usually leave the default configuration unchanged. By default, the token will have all the necessary permissions to operate on your site.

<img class="dropshadow" :src="$withBase('/images/access-token/token-scopes.png')" alt="Screenshot of the Token Scopes option in the Mapbox interface" style="width:850px; max-width:100%;">

### Token Restrictions

If desired, you can optionally restrict the token to only work on specified domains or subdomains...

<img class="dropshadow" :src="$withBase('/images/access-token/token-restrictions.png')" alt="Screenshot of the Token Restrictions options in the Mapbox interface" style="width:850px; max-width:100%;">

## Adding your token to Craft

Once you have created an Access Token, you will need to add it to your Craft site. There are a few different ways to approach this, so go with what works for your setup. The preferred method is to set your token in the project's `.env` file.

You can find the Access Token field on the [Settings > Mapbox](/getting-started/settings/#mapbox-access-token) page in your control panel:

<img class="dropshadow" :src="$withBase('/images/access-token/without-value.png')" alt="Screenshot of a Mapbox Access Token field" style="max-width:600px; margin-top:4px;">

### Setting token in the field directly

:::warning Discouraged - Don't do this
Storing your Access Token directly in the Settings field is discouraged. It is considered a minor security risk, because your unsecured token will end up in the database, as well as in the project config files (and therefore your Git repo).
:::

This is the least preferred method of storing your token. Saving it directly in the Settings field is the least secure solution, and prevents you from using different tokens in different environments.

We do not recommend storing your Access Token directly in the Settings field.

### Setting token in `.env`

:::warning Encouraged - Do this
Using an `.env` file is the most secure and most flexible way to store your Access Token.
:::

**This is the preferred approach.** Presumptively, you already have your database and other system details stored in an `.env` file. Simply add your Access Token to that file, and the settings page will automatically be able to recognize it.

```shell script
# Mapbox Access Token
MAPBOX_ACCESSTOKEN="YOUR_ACCESS_TOKEN"
```

Once that's in place in your `.env` file, it will be possible for the settings page to recognize that value:

<img class="dropshadow" :src="$withBase('/images/access-token/with-value.png')" alt="Screenshot of a Mapbox Access Token field with value from the .env file" style="max-width:600px">

Craft will store a **reference** to the token, instead of storing the token itself. This allows you to keep your unsecured token out of the database, and out of the project config files. It also gives you the flexibility to use different tokens in different environments.

### Setting token in a PHP config file

Lastly, it's possible to store the Access Token in a PHP config file. While this is a solid approach, it doesn't offer many inherent advantages over simply setting your token in the `.env` file.

For more information, read about the [PHP Config File...](/getting-started/config/)
