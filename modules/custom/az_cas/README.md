# Quickstart CAS

Pre-configures contrib CAS module to work with University of Arizona WebAuth.

## Features

- Preconfigures CAS module for University of Arizona WebAuth
- Provides options to disable Drupal login form
- Provides options to disable password recovery
- Provides options to disable adding non-CAS users in admin interface
- Provides guest authentication functionality that allows CAS authentication without creating Drupal user accounts

## Guest Authentication

The guest authentication feature allows site visitors to authenticate via University of Arizona WebAuth without creating individual Drupal user accounts. This is useful for sites that need to restrict content to authenticated NetID users but don't need to store user-specific data or provide personalized experiences.

### How it works

When guest authentication is enabled:

1. Users authenticate through the CAS server as normal
2. Instead of creating a Drupal user account, the module stores the CAS username in the session
3. The user is considered authenticated for CAS-protected resources
4. No Drupal user account is created, so the user doesn't appear in the user administration interface
5. Existing Drupal users can still log in normally with their CAS credentials

### Configuration

1. Go to Administration → Configuration → AZ Quickstart → AZ CAS Settings
2. In the "Guest Authentication" section, check "Enable guest authentication mode"
3. Specify the paths that should be restricted to authenticated NetID users without requiring Drupal accounts (e.g., `/content/*`)
4. Save the configuration

Note: The CAS module's "Auto register users" setting will be automatically enabled when guest mode is activated, as it's required for the guest authentication functionality to work properly.

### Path-based Protection

The guest authentication feature allows you to protect specific paths, restricting access to authenticated NetID users without creating Drupal user accounts:

1. Configure the paths that should be restricted to authenticated NetID users in the AZ CAS settings
2. Anonymous users visiting these paths will be redirected to CAS login
3. After authentication, they'll be redirected back to the original path
4. No Drupal user account is created for these guest users

### Technical details

The guest authentication functionality works by:

1. Intercepting requests to protected paths and redirecting anonymous users to CAS login
2. Preventing user account creation during the CAS authentication process
3. Storing the CAS username in the session to maintain the authenticated state
4. Using the standard Drupal destination parameter to redirect users back to the original path
5. Ensuring that existing Drupal users can still log in normally with their CAS credentials

## Requirements

- Drupal 9 or 10
- CAS module
- AZ Core module
