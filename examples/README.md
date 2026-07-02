# Examples

Runnable scripts demonstrating `rasuvaeff/property-testing`.

| Script | Shows | Needs server? |
|---|---|---|
| `basic.php` | A property that holds, one that is falsified, and how the counterexample is shrunk by descending the `Shrinkable` tree (uses generators directly, no runner) | No |
| `property_test.php` | Canonical `#[Property]` usage as a real Testo test case (run through `vendor/bin/testo`) | No |
| `generators.php` | `sample`, boundary bias, `uuid`, `datetime`, `dictOf`, `record`, and dependent generation with `flatMap` (uses generators directly, no runner) | No |

## Running

The examples are plain PHP scripts that load the package via Composer autoload.
Run them from the package root after `composer install`:

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 php examples/basic.php
```
