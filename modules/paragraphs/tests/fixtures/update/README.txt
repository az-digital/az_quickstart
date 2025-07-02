Steps for creating the database dump:

cd modules/paragraphs/
git checkout 8.x-1.1
cd ../../
git checkout 8.8.0
drush si -y standard --account-pass=admin
drush en -y paragraphs_demo
drush pm-uninstall -y search_api
mkdir -p modules/paragraphs/tests/fixtures/update
php ./core/scripts/db-tools.php dump-database-d8-mysql > modules/paragraphs/tests/fixtures/update/drupal-8.8.standard.paragraphs_demo.php
gzip modules/paragraphs/tests/fixtures/update/drupal-8.8.standard.paragraphs_demo.php
cd modules/paragraphs/
git checkout 8.x-1.x


