# Examples

Runnable scripts demonstrating `rasuvaeff/property-testing`.

| Script | Shows | Needs server? |
|---|---|---|
| `basic.php` | A property that holds, one that is falsified, and how the counterexample is shrunk (uses generators directly, no runner) | No |
| `property_test.php` | Canonical `#[Property]` usage as a real Testo test case (run through `vendor/bin/testo`) | No |
| `generators.php` | The 1.1.0 generators: `sample`, boundary bias, `uuid`, `datetime`, `dictOf`, `record` (uses generators directly, no runner) | No |

## Running

The examples are plain PHP scripts that load the package via Composer autoload.
Run them from the package root after `composer install`:

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 php examples/basic.php
```
