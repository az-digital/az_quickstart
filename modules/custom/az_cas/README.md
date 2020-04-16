# Quickstart CAS Drupal module

Pre-configures the contrib Drupal [CAS module](https://www.drupal.org/project/cas) to work with the University of Arizona's [WebAuth central authentication service](https://it.arizona.edu/documentation/webauth-technical-documentation), accessible via [NetID accounts](https://it.arizona.edu/service/netid-accounts).

## Requirements

### UA WebAuth
In order to use UA WebAuth with your Drupal website, you must first [request WebAuth website access](https://apps.iam.arizona.edu/?&tab=webauthtab).

### Dependencies
This module requires the following modules and libraries to be available.

#### Packaged Dependencies
When this module is used as part of a Drupal distribution (such as [Arizona Quickstart](https://github.com/az-digital/az_quickstart)), the following dependencies will be automatically packaged with the distribution via drush make.

- [CAS module](https://www.drupal.org/project/cas)
- [External Authentication module](https://www.drupal.org/project/externalauth)