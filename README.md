# Arizona Quickstart

Demonstration/starter Drupal distribution and installation profile that packages
various features consistent with community best practices and University of
Arizona brand strategy.

## A Complete Customized Drupal

AZ Quickstart is a complete customized version of the popular Drupal content
management system, not an add-on to an existing Drupal-based web site.
Previously, to start a new Drupal site you would prepare a web server and
database, then install the core software following the instructions in its
[Installing Drupal](https://www.drupal.org/docs/installing-drupal) document and the
[installation chapter](https://www.drupal.org/docs/user_guide/en/installation-chapter.html)
in its user guide.
This would produce a minimally functional web site with no content, which you
could then customize by adding themes and modules to fit it to your needs.
Increasingly, however, you can install Drupal distributions, which not only
contain the core, but also complete sets of additions to make it almost
immediately useful for a particular purpose, such as an online storefront. AZ
Quickstart is the distribution that provides many of the features specific to
the University of Arizona, and rather than an empty site, can include some
demonstration content (which can nevertheless be turned off instantly).

## How to Contribute

See our [contributing](./CONTRIBUTING.md) document for detailed instructions on
how to contribute including:

* What you'll need to [get started](https://github.com/az-digital/az_quickstart/blob/main/CONTRIBUTING.md#things-youll-need-to-get-started)
* [How to create issues and pull requests](https://github.com/az-digital/az_quickstart/blob/main/CONTRIBUTING.md#creating-issues-and-pull-requests)
* [How to submit a pull request](https://github.com/az-digital/az_quickstart/blob/main/CONTRIBUTING.md#pull-request-guidelines)
* [Local development](https://github.com/az-digital/az_quickstart/blob/main/CONTRIBUTING.md#local-development)

## Where to Install Your Quickstart Site

The university's Campus Web Services group provides ready-to-use sites based on
Quickstart for anyone who isn't comfortable with web development or system
administration. Behind the scenes, a web hosting service called Pantheon hosts
these sites, so in cases where people require more control and flexibility
in a Quickstart site than these can provide, there are ways they can also host
it independently on Pantheon, and many important university sites are already
there. However, there are many other options for hosting web sites based on
Arizona Quickstart; the only caveat is that anyone following one of these has
to maintain their responsibility for keeping the system and web site secure
and up-to-date. A generic hosting option that has been around for many years
is known as LAMP: the combination of the Linux operating system, Apache web
server, MySQL database, and PHP scripting language, so it is worth giving a
more extensive example of using this, but many variations are common (for
example, substituting Nginx for the Apache web server software).

### System Requirements

Arizona Quickstart's system requirements are almost identical to
[Drupal's own requirements](https://www.drupal.org/docs/system-requirements),
in particular the requirements for Drupal 9. Some additional things that need
emphasized or checked are:
  * Make sure the system has enough memory: even if the running site consumes little, updates and installations are memory-intensive.
  * Check that PHP's configuration allows it to use the memory (see the [memory requirements](https://www.drupal.org/docs/system-requirements) section).
  * Re-check that the required PHP extensions are added: on a recent system the usual packaging mechanism should suffice to add them (such as apt on Debian or Ubuntu versions of Linux).
  * Check that the Apache configuration enables `mod_rewrite`, and the module letting it work with PHP.
  * Check the user and group of the running web server: in many Debian-derived Linux systems these will be `www-data`, but not on all (this information is needed for setting file and directory permissions later).
  * Install and configure the software as a normal (non-root) user with the ability to `sudo` when elevated privileges are needed; add this user to the same group that the web server uses (so you would see something like `www-data:x:33:normaluser` in the /etc/group file).
  * A recent version of Composer is a necessity, not an option — remove any previously installed but stale versions, and follow the [https://getcomposer.org/download/](https://getcomposer.org/download/) instructions to install it, or if upgrading is possible, try the command `sudo -H composer self-update --2` 

### Web Server Configuration

If your Quickstart site will be exposed to public view, and is anything more
than an ephemeral development site, you should use TLS (SSL, the `https://`
rather than `http://` in the URLs). There are now several easy and cheap ways to
do this, from getting InCommon certicates through the University, the
certificates available in AWS environments, or the free automatically renewable
Let's Encrypt certificates. The Apache default settings will generally need some
hardening (at least disabling obsolete protocols), but there are many guides on
how to do this and [online tools](https://www.ssllabs.com/) that can help. It's
a good practice to wrap the configuration of the Quickstart site in Apache's
`<VirtualHost>` directive, even if there will only be one on the server. There
are options to add security-related headers from within the Quickstart site
itself, but it doesn't hurt to set these in advance, using the Apache
configuration, with things like
```
    Header always set Content-Security-Policy "frame-ancestors 'self'"
    Header always set Strict-Transport-Security "max-age=63072000; includeSubdomains;"
    Header always set X-XSS-Protection 1
```
The more general Drupal
[web server requirements](https://www.drupal.org/docs/system-requirements/web-server-requirements)
are not particularly demanding. Make sure the web server is functioning properly
with a trivial static site before trying the Quickstart installation (an
index.html file stuck somewhere), but note that you should change Apache's
`DocumentRoot` directive for a new location after the initial test: Quickstart's
DocumentRoot will be a directory created during installation (which can't
already exist).

### Database Server Configuration

The main point of Drupal's
[database requirements](https://www.drupal.org/docs/system-requirements/database-server-requirements)
is that it does not support old versions of the server software, but does
support several different variations in the software itself. Quickstart requires
configuration with a MySQL or similar database, and a user set up within the
database server with full rights to access this. In several recent Linux
distributions the ultimate system administration account (root) has full
administrative access to the database server without needing a password, but
there is no sane way to pass these privileges into Quickstart's configuration,
and in any case it is a best practice to create a dedicated administrative user
for this purpose. A typical setup would look like:
```
sudo /usr/bin/mysql -e "DROP DATABASE IF EXISTS azqslampdb;"
sudo /usr/bin/mysql -e "CREATE USER 'azqslampdbadmin'@'localhost' IDENTIFIED BY 'turn_over_an_old_leaf_at_Ardtun';"
sudo /usr/bin/mysql -e "GRANT ALL ON azqslampdb.* TO 'azqslampdbadmin'@'localhost' WITH GRANT OPTION;"
```
Some old code examples combine the `CREATE USER` and `GRANT` in a single
command, but MySQL 8 no longer supports this. `WITH GRANT OPTION` might be
unnecessary in practice, but is not implied by `ALL`.

### Install Using Composer

It's a good idea to make a directory where the non-privileged system user can
write, but where the web server can also access. So for example
```
cd /var/www
sudo mkdir drupalsites
sudo chown normaluser drupalsites
```
It should then be possible to install Quickstart directly in this directory:
```
cd /var/www/drupalsites
composer create-project az-digital/az-quickstart-scaffolding:2.0.x-dev azqs --no-interaction --no-dev
```
This should produce a long list of messages, looking something like
```
Creating a "az-digital/az-quickstart-scaffolding:2.0.x-dev" project at "./azqs"
Installing az-digital/az-quickstart-scaffolding (2.0.x-dev 238cc222d24ca1fdcbd1dbfc5ea249f4ae0ac440)
  - Downloading az-digital/az-quickstart-scaffolding (2.0.x-dev 238cc22)
  - Installing az-digital/az-quickstart-scaffolding (2.0.x-dev 238cc22): Extracting archive
Created project in /var/www/drupalsites/azqs
> QuickstartProject\composer\ScriptHandler::checkComposerVersion
Loading composer repositories with package information
Updating dependencies                                 
Lock file operations: 240 installs, 0 updates, 0 removals
  - Locking alchemy/zippy (0.4.9)
  - Locking asm89/stack-cors (1.3.0)
  - Locking az-digital/arizona-bootstrap (v2.0.11)
  - Locking az-digital/az-quickstart-dev (dev-main 01fcacb)
  - Locking az-digital/az_quickstart (2.0.0-rc1)
 ...
   - Copy [web-root]/site.webmanifest from assets/site.webmanifest
Scaffolding files for az-digital/az-quickstart-scaffolding:
  - Copy [project-root]/.editorconfig from web/core/assets/scaffold/files/editorconfig
  - Copy [project-root]/.gitattributes from web/core/assets/scaffold/files/gitattributes
PHP CodeSniffer Config installed_paths set to ../../drupal/coder/coder_sniffer,../../phpcompatibility/php-compatibility,../../pheromone/phpcs-security-audit,../../sirbrillig/phpcs-variable-analysis
> QuickstartProject\composer\ScriptHandler::createRequiredFiles
Created a sites/default/settings.php file with chmod 0666
Created a sites/default/files directory with chmod 0777
 ```
This will have created a top-level directory (`azqs` in this example), within
which there is a `web` subdirectory serving as the actual DocumentRoot for the
web server. It's important to update the Apache configuration at this point to
reflect this, so in the example there would be a
`DocumentRoot /var/www/drupalsites/azqs/web` directive, and a corresponding
`<Directory /var/www/drupalsites/azqs/web>` (to set things like `AllowOverride All` there).
Once Apache has restarted with the new configuration, there are two ways to
complete the installation. The web site itself will display a variation on the
usual initial Drupal installation form (headed “Arizona Quickstart”), allowing
you to fill in various fields with details such as the database user and
password; but the initial build includes the popular _drush_ utility (down in
the vendor subdirectory), so a purely command-line installation is possible with
something like
 ```
 /var/www/drupalsites/azqs/vendor/drush/drush/drush si --db-url=mysql://azqslampdbadmin:turn_over_an_old_leaf_at_Ardtun@localhost/azqslampdb --account-name=azadmin --account-pass=flour_85_percent_extraction --account-mail=webmaster@lamp.arizona.edu --site-mail=admin@development.lamp.arizona.edu --site-name='LAMP Development' --verbose --yes az_quickstart
```
In this example, note that the database credentials match those set previously
(other details shown here should also be customized with your own settings). One
messy detail at the moment is that you may have to manually create a top-level
`config/sync` directory (so /var/www/drupalsites/azqs/config/sync in this
example). If all goes well, you will be able log in to the new site with the
username and password you have set. One thing that will need immediate attention
is the report at `/admin/reports/status#error` once you have logged in. There
will probably be a Trusted Host Settings error, needing a manual change to your
settings file, and notice of some directory permissions that need relaxed to
allow the web server to write there.

### Using Xdebug with Lando and VSCode

This repository contains the necessary config to run Xdebug inside a lando
application container.

To start a debugging session, perform the following steps:
1. In VSCode, go to the extensions tab.
2. Type `@recommended` in the search bar.
3. Install all workspace recommended extensions.
4. Start lando with `lando start`.
5. Start Xdebug with `lando xdebug-on`.
6. At the bottom left of your VSCode window, click the little green icon.
7. Choose `Attach to Running Container...` and select the `appserver` for your running lando instance.
8. Once the new VScode window pops up, go back to the extensions tab.
9. Type `@recommended` in the search bar.
10. Install all workspace recommended extensions.
11. In VSCode, go to the debugging tab.
12. Click the green triangle next to `Listen for XDebug` at the top right.
13. In VSCode, go back to the code tab.
14. Set any desired breakpoints.
15. You may now proceed with debugging.

### Distribution update

When updating your codebase on an existing site, you should always check if
there are distribution updates that need to be applied.

This can be done by users with the administrator role on your website at this
path: `/admin/config/development/distro`

You should be able to see the upstream updates to be applied, after updating
your codebase by employing the "Merge" strategy available under the "Advanced"
accordion on that page.
