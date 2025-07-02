Steps for updating libraries
----------------------------

  1. Create a ticket in the Webform issue queue
  2. Create a list of all recent releases
  3. Update WebformLibrariesManager
  4. Update webform.libraries.yml
  5. Test changes
  6. Update webform_libraries.module
  7. Update composer.libraries.json


1. Create a ticket in the Webform issue queue
----------------------------------------------

- https://www.drupal.org/node/add/project-issue/webform


2. Create a list of all recent releases
---------------------------------------

- Enable all external libraries (admin/structure/webform/config/libraries)

- Manually check for new releases. Only update to stable releases.
  - @see <https://stackoverflow.com/questions/15035786/download-source-from-npm-without-installing-it>

- Add list of updated external libraries to issue on Drupal.org


3. Update WebformLibrariesManager
---------------------------------

- \Drupal\webform\WebformLibrariesManager::initLibraries


4. Update webform.libraries.yml
---------------------------------

- webform.libraries.yml


5. Test changes
---------------

Check external libraries are loaded from CDN.

    drush webform:libraries:remove

Check external libraries are download.

    drush webform:libraries:download


6. Update webform_libraries.module
----------------------------------

Enable and download all libraries

    cd ~/Sites/drupal_webform
    drush php-eval "\Drupal::configFactory()->getEditable('webform.settings')->set('libraries.excluded_libraries', [])->save();"
    drush en -y webform_image_select webform_toggles webform_location_geocomplete webform_location_places webform_icheck webform_options_custom
    drush webform:libraries:download

Update libraries.zip

    # Checkout branch
    cd ~/Sites/drupal_webform/web/modules/sandbox/webform_libraries/
    git checkout 6.2.x

    # Remove libraries.zip
    cd ~/Sites/drupal_webform/web/modules/sandbox/webform_libraries/
    rm -Rf libraries.zip

    # Create libraries.zip
    cd ~/Sites/drupal_webform/web/
    zip -r libraries.zip libraries
    mv libraries.zip ~/Sites/drupal_webform/web/modules/sandbox/webform_libraries/libraries.zip

Commit changes

    # Commit changes.
    cd ~/Sites/drupal_webform/web/modules/sandbox/webform_libraries/
    git checkout 6.2.x
    git commit -am"Update webform_libraries"
    git push


7. Update composer.libraries.json
----------------------------------

    cd ~/Sites/drupal_webform/web/modules/sandbox/webform
    drush webform:libraries:composer > composer.libraries.json
