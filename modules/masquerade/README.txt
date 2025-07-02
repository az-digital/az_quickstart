
-- SUMMARY --

The Masquerade module allows users to temporarily switch to another user
account. It keeps a record of the original user account, so users can easily
switch back to the previous account.

For a full description of the module, visit the project page:
  http://drupal.org/project/masquerade

To submit bug reports and feature suggestions, or to track changes:
  http://drupal.org/project/issues/masquerade


-- REQUIREMENTS --

None.


-- INSTALLATION --

* Install as usual, see
  https://www.drupal.org/docs/extending-drupal/installing-modules

* Grant the "Masquerade as another user" permission to the desired roles.


-- SECURITY --

* Masquerade's built-in access control mechanism has been designed to be simple,
  smart, and secure by default:

  - Users without the masquerade permission are not allowed to masquerade.
  - Uid 1 may masquerade as anyone.  No one can masquerade as uid 1.
  - If you have the identical permissions as the target user (or additional
    permissions), you are allowed to masquerade.
  - Otherwise, access to masquerade as the target user is denied.

  This means that Masquerade's built-in access control does not allow any kind
  of privilege escalation.  It is safe to grant the masquerade permission to
  user roles.  Users are never able to exceed their privileges by masquerading
  as someone else.

* More fine-grained access control (e.g., role-per-role, per-user, blacklist)
  may be supplied by separate add-on modules for Masquerade.


-- FEATURES AND INTEGRATION --

* The Masquerade module provides and aims for a deep integration with the
  built-in user interface of Drupal core and popular contributed administration
  interface modules:

  - Contextual links (core)
  - Toolbar (core)
  - Administration menu (http://drupal.org/project/admin_menu)

* Aside from its user permission, the Masquerade module aims for a smart and
  intuitive integration that does not require any configuration.  Its design and
  architecture tries to meet the expectations of these user stories:

  - This is helpful. Even though I don't know whether I'll actually need it.
  - This is secure. 100% test coverage for the limited functionality
    it provides.
  - This isn't bloat. Super small, dead simple, focused on the >80% task.
  - This is friendly. Available when I need it, close to "zero-conf", and has
    absolutely no other UI implications.

* Masquerading as Anonymous user is intentionally not supported. Likewise, more
  granular user permissions and other custom plumbing needs to be implemented in
  separate modules instead.


-- CONTACT --

Current maintainers:
* Daniel F. Kudwien (sun) - http://drupal.org/user/54136
* Andrey Postnikov (andypost) - http://drupal.org/user/118908
