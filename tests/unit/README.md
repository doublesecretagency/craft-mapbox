# Unit tests

PHPUnit 10, no Craft bootstrap. Run from inside the DDEV container:

```bash
ddev ssh
cd vendor/doublesecretagency/craft-mapbox
composer test
```

Or as a one-shot from the sandbox directory on the host:

```bash
ddev exec bash -c "cd /var/www/html/vendor/doublesecretagency/craft-mapbox && /var/www/html/vendor/bin/phpunit"
```

About 470 tests in well under a second. If it gets slow, something is
bootstrapping more than intended.

## Why there is no Craft container

Booting Craft would make the suite slower, order-dependent, and reliant on a
database. Everything here either runs as pure PHP or inspects source text.

That draws a hard line around what can be tested. A method that dereferences
`Craft::$app` gets a structural test instead of a behavioral one, and
`SuiteIntegrityTest` enforces the boundary by failing if any test file
references the container. Two Craft helpers look harmless and are not:
`Template::raw()` (so `Address::multiline()`) and `UrlHelper::urlWithParams()`
with a non-empty query both reach for `Craft`.

## Three categories

Every test fits one of these. Mixing them in one file is fine; mixing them
inside one test method is a smell.

**Pure unit.** Real behavior, called directly. `Location`, most of `Address`,
`Defaults`, `MapHelper::extractCoords()`, the subfield-config normalizer, the
DNA a `DynamicMap` records, the map registry on the `Mapbox` facade, and the
validator's error messages.

**Reflection and shape.** For anything coupled to Craft's container. Asserts
inheritance, method existence and signatures, and property defaults read through
`getDefaultProperties()` rather than by constructing the object.

**Source-level.** For cases where the shape of the code is the thing that
matters: the address keys repeated across ten files, PHP/JS parity for the map
methods, the install schema, the asset bundles, the event registrations in
`init()`, and the Twig templates.

## No exclusions

`phpunit.xml` excludes nothing, and `SuiteIntegrityTest` asserts that it stays
that way.

## How we know it can fail

A suite is green from the moment it is written, so a passing first run is
evidence about nothing. This one was fired at deliberate defects before being
called done: 27 mutations, one at a time, each injected into the plugin source
with the suite run against it and the file then restored.

**26 of 27 were caught on the first pass, and the 27th now is.** Among them: the
Bermuda default moved to 0,0, `neighborhood` dropped from the save, `county`
dropped from the Pinia store's search mapping, `lat` narrowed to
`decimal(10,6)`, a foreign key's `CASCADE` removed, `zoom` dropped from the
field type conversion, `panToMarker` renamed in JavaScript only, the `fit` case
removed from `_unpack()`, a method renamed in a `.min.js` twin only, one GL pin
bumped, the old-style subfield sort flipped, and `hasCoords()` rejecting 0,0.

The miss was instructive. Renaming `Sortable.min.js` to `sortable.min.js` in a
bundle passed, because macOS resolves the wrong case and a Linux server would
not. The file check now compares against the directory listing as well, and
catches both the wrong-case and the plainly missing name.

🛑 The harness writes broken code to disk, so never run it on a dirty `src/`
tree. The recipe is in `~/.claude/instructions/plugin-tests.md`.

## What this suite catches

- An Address subfield added or renamed in some places but not all of them
  (`AddressContractParityTest`)
- A map method added to PHP but not JavaScript, or not replayed by `mapbox.js`
- A source JS edit that skipped regenerating its `.min.js` twin (method and
  case lists are compared exactly)
- Mapbox GL JS pins drifting apart
- A bundle naming a file that doesn't exist
- Schema drift between the install migration, the upgrade migration, the
  record and the model
- A column added to the field type conversion that one of the tables lacks
- Missing event registrations in the plugin's `init()`
- Setting defaults changing, or the config template falling out of step

## What it does not catch

- Anything that talks to Mapbox: search results, map rendering, real HTTP
- Database query correctness
- Whether a Twig template parses at all. A source-level test reads a template as
  text, and a template throwing a syntax error on every request satisfies every
  assertion written about it.
- The Vue components at runtime. A test can say a binding is declared, not that
  it hydrated.

For all of those, exercise the flow in the sandbox.

## Known gaps

One open bug is deliberately not asserted yet. It gets its regression test in
the same change that fixes it, so the suite never starts red and never locks a
bug in.

- **`Location::getDistance()` always returns 0** (findings #3). The earth radius
  is hardcoded to zero. `LocationModelTest` covers only the null paths.
