# Quickstart CAS

Pre-configures contrib CAS module to work with University of Arizona WebAuth.

## Features

- Preconfigures CAS module for University of Arizona WebAuth
- Provides options to disable Drupal login form
- Provides options to disable password recovery
- Provides options to disable adding non-CAS users in admin interface
- Provides guest authentication functionality that allows CAS authentication without creating Drupal user accounts

## Guest Authentication

The guest authentication feature allows site visitors to authenticate via Quickstart CAS without creating individual Drupal user accounts. This is useful for sites that need to authenticate users but don't need to store user-specific data or provide personalized experiences.

### How it works

When guest authentication is enabled:

1. Users authenticate through the CAS server as normal
2. Instead of creating a Drupal user account, the module stores the CAS username in the session
3. The user is considered authenticated for CAS-protected resources
4. No Drupal user account is created, so the user doesn't appear in the user administration interface

### Configuration

1. Go to Administration → Configuration → AZ Quickstart → AZ CAS Settings
2. In the "Guest Authentication" section, check "Enable guest authentication mode"
3. Save the configuration

### Technical details

The guest authentication functionality works by intercepting the CAS authentication process at the pre-register stage. When a user authenticates through CAS and no matching Drupal account exists, the module prevents the creation of a new account and instead stores the CAS username in the session.

## Requirements

- Drupal 9 or 10
- CAS module
- AZ Core module