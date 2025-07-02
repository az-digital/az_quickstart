A demonstration of a simple import of a JSON file.

REQUIREMENTS
============
You need the contrib modules Migrate Plus and Migrate Tools.
To make the products.json file available for import, the file will be copied
from the artifacts folder to your sites/default/files folder.

USAGE
=====
Enable the module, check status, import all products and rollback with Drush
drush en migrate_json_example
drush migrate-status
drush migrate-import product
drush migrate-rollback product

See config/optional/migrate_plus.migration.product.yml for details about the
migration.

Thanks to Jeff Geerling and Christophe for the original code:
https://www.jeffgeerling.com/blog/2016/migrate-custom-json-feed-drupal-8-migrate-source-json

https://colorfield.be/blog/drupal-8-json-custom-migration
