PrestaShop Repositories Bulk Editor
===================================

A basic script used to perform repositories management operations.

Currently, it can go through given repositories
and create a PR to add files.

Example of files:
- `.github/PULL_REQUEST_TEMPLATE.md`
- `.github/release-drafter.yml`
- `LICENSE.md`

# Usage

## Create missing file

Put a GitHub token in file `token.txt` then run `$ php create-missing-file.php`

## Generate contributors list

Put a GitHub token in file `token.txt` then run `$ php create-contributors.php`
