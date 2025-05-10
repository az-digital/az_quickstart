# Quickstart CAS Guest Authentication

## Overview

This module allows site visitors to authenticate via the University of Arizona's Quickstart CAS authentication service without requiring Drupal user accounts to be created for them. It works with the Quickstart CAS module.

## Features

- Authenticate users via Quickstart CAS without creating individual Drupal user accounts
- Existing Drupal users can still log in normally with their CAS credentials
- Store CAS authentication information in the session
- Works with CAS forced login paths - authenticated guest users can access protected paths
- Seamless integration with the existing Quickstart CAS module

## Requirements

- Drupal 10
- Quickstart CAS module

## Installation

1. Install the module as you would normally install a Drupal module.
2. Enable the module at `/admin/modules` or using Drush: `drush en az_cas_guest`
3. Configure the module at `/admin/config/people/cas/guest`

## Configuration

1. Go to `/admin/config/people/cas/guest`
2. Check "Enable guest authentication mode" to activate the feature
3. Configure forced login paths in the Quickstart CAS module settings at `/admin/config/people/cas`

## How it works

When enabled, this module intercepts the CAS authentication process using the Quickstart CAS module's event system. It checks if a Drupal user account already exists for the CAS username:

- If a user account exists, the standard CAS login flow is used
- If no user account exists and guest mode is enabled:
  - User creation is prevented
  - The user remains anonymous but is authenticated via CAS
  - The CAS username is stored in the session

For forced login paths:
- The module checks if the user is already authenticated as a CAS guest
- If they are, they can access the protected path without being redirected to the CAS server again

This approach allows you to:
1. Create individual Drupal accounts for users who need specific roles/permissions
2. Keep other users as authenticated guests without creating Drupal accounts
3. Keep track of CAS usernames for all users
4. Protect specific paths with CAS authentication

## Usage in code

You can use the CasGuestAuthenticationService to check if the current session is authenticated via CAS:

```php
$is_guest = \Drupal::service('az_cas_guest.authentication')->isGuestSession();
$cas_username = \Drupal::service('az_cas_guest.authentication')->getGuestUsername();

if ($is_guest) {
  // User is authenticated via CAS but doesn't have a Drupal account.
  // $cas_username contains the CAS username.
}
```

## Maintainers

- Your Name - https://www.drupal.org/u/your-drupal-username