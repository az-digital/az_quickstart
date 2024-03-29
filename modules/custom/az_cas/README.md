# Quickstart CAS Drupal module

Pre-configures the contrib Drupal [CAS module](https://www.drupal.org/project/cas) to work with the University of Arizona's [WebAuth central authentication service](https://it.arizona.edu/documentation/webauth-technical-documentation), accessible via [NetID accounts](https://it.arizona.edu/service/netid-accounts).

## Requirements

### University of Arizona WebAuth
If your website is hosted at an `arizona.edu` domain, and supports SSL/TLS, you do not need to register your website with WebAuth. If your website doesn't match the pattern of `https://*.arizona.edu/` you can [request SSO Access in ServiceNow](https://uarizona.service-now.com/sp?id=sc_cat_item&sys_id=203b92371bdda1104addfe6edd4bcbd2).

### Dependencies
This module requires the following modules and libraries to be available.

#### Packaged Dependencies
- [CAS module](https://www.drupal.org/project/cas)
- [External Authentication module](https://www.drupal.org/project/externalauth)
