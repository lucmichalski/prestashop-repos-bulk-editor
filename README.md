PrestaShop Repositories Bulk Editor
===================================

A basic script used to perform repositories management operations.

For example, it can go through given repositories
and create a PR to add files or fix outdated license headers.

Example of files:
- `.github/PULL_REQUEST_TEMPLATE.md`
- `.github/release-drafter.yml`
- `LICENSE.md`

# Usage

## Create missing file

Put a GitHub token in file `token.txt` then run `$ php create-missing-file.php`

## Generate contributors list

Put a GitHub token in file `token.txt` then run `$ php create-contributors.php`

## Fix outdated license headers

Put a GitHub token in file `token.txt` then run `$ php fix-license-header.php`

## Reset forks

Reset a fork mean:
- delete fork `dev` branch
- pull upstream `dev` branch
- create new fork `dev` branch from it

This aims to make sure fork `dev` is up-to-date.

Put a GitHub token in file `token.txt` then run `$ php reset-forks.php`
