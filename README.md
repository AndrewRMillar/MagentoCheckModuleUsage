# Magento 2 Module: Check Module Usage

## Overview

This Magento 2 CLI module helps determine whether a specific module is in use within a Magento installation. It checks:

- If the module is enabled.
- If the module has active configuration settings.
- If the module has database tables.
- If the module is referenced in theme files.

## Installation

### Copy or use composer

Place the module in the `app/code/` directory and activate it through `bin/magento module:enable Vendor_ModuleCheck`. Or better yet use `composer install`

- `composer require andrewrmillar/magento-check-module-usage`
- `bin/magento setup:upgrade`

## Usage

To check if a module is in use, run:

```sh
bin/magento module:check-usage Vendor_Module
```

Replace `Vendor_Module` with the actual module name (e.g., `Magento_Catalog`).

## Features

- **Checks if the module is enabled** using Magento's module registry.
- **Verifies module configuration settings** dynamically.
- **Checks for module-related database tables**.
- **Scans frontend theme files** for module references.

## Example Output

```
Checking module: Vendor_Module
Module is enabled.
Module has active config settings:
path/to/some/config/value = 1
Found module-related database tables.
Module is referenced in theme files.
Check complete.
```

## Uninstallation

To remove the module:

```sh
composer remove andrewrmillar/magento-check-module-usage
bin/magento setup:upgrade
```

## License

This module is open-source and provided under the MIT License.

## Author

Developed by [AndrewRMillar](https://github.com/AndrewRMillar). Contributions are welcome!
