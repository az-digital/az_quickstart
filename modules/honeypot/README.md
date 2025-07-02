
# Honeypot

[![Build Status](https://travis-ci.org/geerlingguy/drupal-honeypot.svg?branch=8.x-1.x)](https://travis-ci.org/geerlingguy/drupal-honeypot)


## Installation

To install this module, `composer require` it, or place it in your modules
folder and enable it on the modules page.


## Configuration

All settings for this module are on the Honeypot configuration page, under the
Configuration section, in the Content authoring settings. You can visit the
configuration page directly at /admin/config/content/honeypot.

Note that, when testing Honeypot on your website, make sure you're not logged in
as an administrative user or user 1; Honeypot allows administrative users to
bypass Honeypot protection, so by default, Honeypot will not be added to forms
accessed by site administrators.


## Use in Your Own Forms

If you want to add honeypot to your own forms, or to any form through your own
module's hook_form_alter's, you can simply place the following function call
inside your form builder function (or inside a hook_form_alter):

    \Drupal::service('honeypot')->addFormProtection(
      $form,
      $form_state,
      ['honeypot', 'time_restriction']
    );

Note that you can enable or disable either the honeypot field, or the time
restriction on the form by including or not including the option in the array.


## Testing

Honeypot includes a `docker-compose.yml` file that can be used for testing
purposes. To build a Drupal 8 environment for local testing, do the following:

  1. Make sure you have Docker installed.
  1. Run the following commands in this directory to start the environment and
     install Drush:

     ```
     docker-compose up -d
     # Wait a couple minutes for the container to build the Drupal codebase.
     docker-compose exec drupal bash -c 'composer require drush/drush'
     ```

  1. Link the honeypot module directory into the Drupal modules directory:

     ```
     docker-compose exec drupal ln -s /opt/honeypot/ /var/www/html/web/modules/honeypot
     ```

  1. Install Drupal with Drush:

     ```
     docker-compose exec drupal bash -c 'vendor/bin/drush site:install standard --site-name="Honeypot Test" --account-pass admin -y && chown -R www-data:www-data web/sites/default/files'
     ```

  1. Log into `http://localhost/` with `admin`/`admin` and enable Honeypot (and
     the Testing module, if desired).

## Credit

The Honeypot module was originally developed by Jeff Geerling of [Midwestern Mac,
LLC](https://www.midwesternmac.com/), and sponsored by [Flocknote](https://flocknote.com).
