Steps for creating a new release
--------------------------------

  1. Review code
  2. Deprecated code
  3. Review accessibility
  4. Run tests
  5. Generate release notes
  6. Tag and create a new release
  7. Tag and create a hotfix release

1. Review code
--------------

    # Remove files that should never be reviewed.
    cd modules/sandbox/webform
    rm *.patch interdiff-*

[PHP](https://www.drupal.org/node/1587138)

    # Check Drupal PHP coding standards and best practices.
    phpcs .

    # Show sniff codes in all reports.
    phpcs -s .

    # Install PHP version compatibility (One-time)
    cd ~/Sites/drupal_webform
    composer require --dev phpcompatibility/php-compatibility

    # Check PHP version compatibility
    cd ~/Sites/drupal_webform/web
    phpcs --runtime-set testVersion 8.0 --standard=../vendor/phpcompatibility/php-compatibility/PHPCompatibility --extensions=php,module,inc,install,test,profile,theme modules/sandbox/webform > ~/webform-php-compatibility.txt
    cat ~/webform-php-compatibility.txt

[JavaScript](https://www.drupal.org/node/2873849)

    # Install Eslint. (One-time)
    cd ~/Sites/drupal_webform/web/core
    yarn install

    # Check Drupal JavaScript (ES5) legacy coding standards.
    cd ~/Sites/drupal_webform/web
    core/node_modules/.bin/eslint --no-eslintrc -c=core/.eslintrc.legacy.json --ext=.js modules/sandbox/webform > ~/webform-javascript-coding-standards.txt
    cat ~/webform-javascript-coding-standards.txt

[CSS](https://www.drupal.org/node/3041002)

    # Install Eslint. (One-time)
    cd ~/Sites/drupal_webform/web/core
    yarn install

    cd ~/Sites/drupal_webform/web/core
    yarn run lint:css ../modules/sandbox/webform/css --fix

[Spell Check](https://www.drupal.org/node/3122084) for Drupal 9.1+

    # Install Pspell. (One-time)
    cd ~/Sites/drupal_webform/web/core
    yarn install

    # Update dictionary. (core/misc/cspell/dictionary.txt)

    cd ~/Sites/drupal_webform/web/
    cat modules/sandbox/webform/cspell/dictionary.txt >> core/misc/cspell/dictionary.txt

    cd ~/Sites/drupal_webform/web/core
    yarn run spellcheck ../modules/sandbox/webform/**/* > ~/webform-spell-check.txt
    cat ~/webform-spell-check.txt

[File Permissions](https://www.drupal.org/comment/reply/2690335#comment-form)

    # Files should be 644 or -rw-r--r--
    find * -type d -print0 | xargs -0 chmod 0755

    # Directories should be 755 or drwxr-xr-x
    find . -type f -print0 | xargs -0 chmod 0644

2. Deprecated code
------------------

[drupal-check](https://mglaman.dev/blog/tighten-your-drupal-code-using-phpstan) - RECOMMENDED

Install PHPStan

    cd ~/Sites/drupal_webform
    composer require composer require \
      phpstan/phpstan \
      phpstan/extension-installer \
      phpstan/phpstan-deprecation-rules \
      mglaman/phpstan-drupal

Run PHPStan with level 2 to catch all deprecations.
@see <https://phpstan.org/user-guide/rule-levels>

    cd ~/Sites/drupal_webform
    ./vendor/bin/phpstan --level=2 analyse web/modules/sandbox/webform > ~/webform-deprecated.txt
    cat ~/webform-deprecated.txt

[Drupal Rector](https://github.com/palantirnet/drupal-rector)

Install Drupal Rector

    cd ~/Sites/drupal_webform
    composer require palantirnet/drupal-rector --dev
    cp vendor/palantirnet/drupal-rector/rector.php .

Run Drupal Rector

    cd ~/Sites/drupal_webform
    ./vendor/bin/rector process web/modules/sandbox/webform --dry-run
    ./vendor/bin/rector process web/modules/sandbox/webform

3. Review accessibility
-----------------------

[Pa11y](http://pa11y.org/)

Pa11y is your automated accessibility testing pal.

    # Enable accessibility examples.
    drush en -y webform_examples_accessibility

    # Text.
    mkdir -p ~/Sites/drupal_webform/web/modules/sandbox/webform/reports/accessiblity/text
    cd ~/Sites/drupal_webform/web/modules/sandbox/webform/reports/accessiblity/text
    pa11y http://localhost/wf/webform/example_accessibility_basic > example_accessibility_basic.txt
    pa11y http://localhost/wf/webform/example_accessibility_advanced > example_accessibility_advanced.txt
    pa11y http://localhost/wf/webform/example_accessibility_containers > example_accessibility_containers.txt
    pa11y http://localhost/wf/webform/example_accessibility_wizard > example_accessibility_wizard.txt
    pa11y http://localhost/wf/webform/example_accessibility_labels > example_accessibility_labels.txt

    # HTML.
    mkdir -p ~/Sites/drupal_webform/web/modules/sandbox/webform/reports/accessiblity/html
    cd ~/Sites/drupal_webform/web/modules/sandbox/webform/reports/accessiblity/html
    pa11y --reporter html http://localhost/wf/webform/example_accessibility_basic > example_accessibility_basic.html
    pa11y --reporter html http://localhost/wf/webform/example_accessibility_advanced > example_accessibility_advanced.html
    pa11y --reporter html http://localhost/wf/webform/example_accessibility_containers > example_accessibility_containers.html
    pa11y --reporter html http://localhost/wf/webform/example_accessibility_wizard > example_accessibility_wizard.html
    pa11y --reporter html http://localhost/wf/webform/example_accessibility_labels > example_accessibility_labels.html

    # Remove localhost from reports.
    cd ~/Sites/drupal_webform/web/modules/sandbox/webform/reports/accessiblity
    find . -name '*.html' -exec sed -i '' -e  's|http://localhost/wf/webform/|http://localhost/webform/|g' {} \;

    # PDF.
    mkdir -p ~/Sites/drupal_webform/web/modules/sandbox/webform/reports/accessiblity/pdf
    cd ~/Sites/drupal_webform/web/modules/sandbox/webform/reports/accessiblity/pdf
    wkhtmltopdf --dpi 384 ../html/example_accessibility_basic.html example_accessibility_basic.pdf
    wkhtmltopdf --dpi 384 ../html/example_accessibility_advanced.html example_accessibility_advanced.pdf
    wkhtmltopdf --dpi 384 ../html/example_accessibility_containers.html example_accessibility_containers.pdf
    wkhtmltopdf --dpi 384 ../html/example_accessibility_wizard.html example_accessibility_wizard.pdf
    wkhtmltopdf --dpi 384 ../html/example_accessibility_labels.html example_accessibility_labels.pdf


4. Run tests
------------

[SimpleTest](https://www.drupal.org/node/645286)

    # Run all tests
    cd ~/Sites/drupal_webform
    php core/scripts/run-tests.sh --suppress-deprecations --url http://localhost/wf --module webform --dburl mysql://drupal_d8_webform:drupal.@dm1n@localhost/drupal_d8_webform

    # Run single tests
    cd ~/Sites/drupal_webform
    php core/scripts/run-tests.sh --suppress-deprecations --url http://localhost/wf --verbose --class "Drupal\Tests\webform\Functional\WebformListBuilderTest"

[PHPUnit](https://www.drupal.org/node/2116263)

Notes
- Links to PHP Unit HTML responses are not being printed by PHPStorm

References
- [Issue #2870145: Set printerClass in phpunit.xml.dist](https://www.drupal.org/node/2870145)
- [Lesson 10.2 - Unit testing](https://docs.acquia.com/article/lesson-102-unit-testing)


    # Export database and base URL.
    export SIMPLETEST_DB=mysql://drupal_d8_webform:drupal.@dm1n@localhost/drupal_d8_webform;
    export SIMPLETEST_BASE_URL='http://localhost/wf';

    # Execute all Webform PHPUnit tests.
    cd ~/Sites/drupal_webform/web/core
    php ../../vendor/phpunit/phpunit/phpunit --printer="\Drupal\Tests\Listeners\HtmlOutputPrinter" --group webform

    # Execute individual PHPUnit tests.
    cd ~/Sites/drupal_webform/web/core

    # Functional test.
    php ../../vendor/phpunit/phpunit/phpunit --printer="\Drupal\Tests\Listeners\HtmlOutputPrinter" ../modules/sandbox/webform/tests/src/Functional/WebformExampleFunctionalTest.php

    # Kernal test.
    php ../../vendor/phpunit/phpunit/phpunit --printer="\Drupal\Tests\Listeners\HtmlOutputPrinter" ../modules/sandbox/webform/tests/src/Kernal/Utility/WebformDialogHelperTest.php

    # Unit test.
    php ../../vendor/phpunit/phpunit/phpunit --printer="\Drupal\Tests\Listeners\HtmlOutputPrinter" ../modules/sandbox/webform/tests/src/Unit/Utility/WebformYamlTest.php

    php ../../vendor/phpunit/phpunit/phpunit --printer="\Drupal\Tests\Listeners\HtmlOutputPrinter" ../modules/sandbox/webform/tests/src/Unit/Access/WebformAccessCheckTest


5. Generate release notes
-------------------------

[Git Release Notes for Drush](https://www.drupal.org/project/grn)
[Generate release notes](https://drupal-mrn.dev/)


    drush release-notes --nouser 6.2.x 6.2.x
    drush release-notes --nouser 6.1.x 6.2.x


6. Tag and create a new release
-------------------------------

[Tag a release](https://www.drupal.org/node/1066342)

    git checkout 6.2.x
    git up
    git tag 6.2.x-VERSION
    git push --tags
    git push origin tag 6.2.x-VERSION


[Create new release](https://www.drupal.org/node/add/project-release/2640714)


7. Tag and create a hotfix release
----------------------------------

    # Creete hotfix branch
    git checkout 6.2.LATEST-VERSION
    git checkout -b 6.2.NEXT-VERSION-hotfix
    git push -u origin 6.2.NEXT-VERSION-hotfix

    # Apply and commit remote patch
    curl https://www.drupal.org/files/issues/[project_name]-[issue-description]-[issue-number]-00.patch | git apply -
    git commit -am 'Issue #[issue-number]: [issue-description]'
    git push

    # Tag hotfix release.
    git tag 6.2.NEXT-VERSION
    git push --tags
    git push origin tag 6.2.NEXT-VERSION

    # Merge hotfix release with HEAD.
    git checkout 6.2.x
    git merge 6.2.NEXT-VERSION-hotfix

    # Delete hotfix release.
    git branch -D 6.2.NEXT-VERSION-hotfix
    git push origin :6.2.NEXT-VERSION-hotfix
