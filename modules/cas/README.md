# CAS

As described in the official CAS protocol documentation:

"The Central Authentication Service (CAS) is a single-sign-on /
single-sign-on protocol for the web. It permits a user to access
multiple applications while providing their credentials (such as userid
and password) only once to a central CAS Server application."

Using a single-sign on service like CAS is a beneficial because it provides:

- Convenience. Your users don't need to remember credentials for multiple
  different web services.
- Security. Your Drupal website never sees a user's password.

This module implements version 1 and version 2 of the CAS protocol:
https://apereo.github.io/cas/7.0.x/protocol/CAS-Protocol.html

Not all parts of the specification are implemented, but the core functionality
that the protocol describes works well.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/cas).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/cas).


## Requirements

This module requires the following modules:

- [External Authentication](https://www.drupal.org/project/externalauth)


## Recommended Modules

- [CAS Attributes](http://drupal.org/project/cas_attributes) allows user
attributes and roles to be set based on attributes provided by the cas server.


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

The configuration page for this module is in /admin/config/people/cas, and can be accessed in the admin menu under Configuration -> People -> CAS.


### Getting Started and Basic Usage

All of the settings for this module are on the single configuration page
described above. To get started, you simply need to configure the settings
for your CAS server.

The only CAS server setting that may not be easily understood is the
"SSL Verification" setting. In most cases, the default setting (using
server defaults) will work fine. If not, read the "SSL Verification Setting"
section below to learn more.

This module exposes a specific URL path on your website that will trigger
the CAS authentication process for your users:

http://yoursite.com/cas (/caslogin will also work)

Users will be redirected to your CAS server to authenticate. If they already
have an active session with the CAS server, they will immediately be redirected
back to your site and authenticated seamlessly. If not, they will be prompted
to enter their credentials and then redirected back to your Drupal site and
authenticated.

You can create a login button on your website that links directly to this
path to provide a way for your users to easily login.


### Account Handling & Auto Registration

Local Drupal accounts must exist for users that authenticate with CAS.
This module simply provides a way to authenticate these users.

If a user attempts to login with an account that is not already registered on
your Drupal site, they will see an error message.

However, you can configure the module to automatically register users when
they log in via CAS for the first time. A local Drupal account will
automatically be created for that user. The password for the account will
be randomly generated and is not revealed to the user.

You can configure this module to prevent CAS users from changing their password,
using the password reset form, changing their email, and logging in using the
normal Drupal login form. All of those options are recommended and enabled
by default.


### Forced Login

You can enable the Forced Login feature to force anonymous users to
authenticate via CAS when they visit all or some of the pages on your site.


### Gateway Login

With this feature enabled, anonymous users that visit some or all pages on
your site will automatically be logged in IF they already have an active
CAS session with the CAS server.

If the user does not have an active session with the CAS server, they will
see the Drupal page requested as normal.

This feature works by quickly redirecting the user to the CAS server to
check for an active session, and then redirecting back to the page they
originally requested on your website.

This feature differs from Forced Login in that it will not force the user
to login if they do not already have an active CAS server session.

*This feature is not currently compatible with any form of page caching
and is not recommended.*


### SSL Verification Setting

This module makes an HTTP request to your CAS server during the authentication
process, and since CAS servers must be accessed over HTTPS, this module needs
to know how to verify the SSL/TLS certificate of your CAS server to be
sure it is authentic.

Assuming the SSL cert of your CAS server was signed by a well known
certificate authority, CAS will use the default certificate chain that
should be present on your web server and it will "just work". But if you are
getting errors when authenticating, you may need obtain the PEM certificate
of the certificate authority that signed your CAS server's SSL cert and
provide the path to that cert.

Further discussion of this topic is beyond the scope of this documentation,
but web hosts and system administrators should have a deep understanding
of this topic to help further.


### Integration with the "Redirect 402 to User Login" (r4032login) module

It is often useful to have access denied pages automatically attempt to
authenticate users via CAS. This provides a seamless login experience for
your visitors when they try and access a page that is restricted.

For example, imagine you have a Webform submission that emails site
administrators a link to review submissions as they are received.
When a logged out admin visits the link, they would normally be met with
the 403 Access Denied page, but a better experience may be to instead
automatically log them in via CAS.

To enable this behavior, enable the r4032login module and configure it as such:

1. Check the "Redirect user to the page they tried to access after login"
   checkbox
1. Set the "Path to user login form" to "/caslogin"


### Proxy

Initializing a CAS client as a proxy allows the client to make web service calls
to other sites or web pages that are protected by cas authentication.  It
is often used in portal applications allowing the portal product to get
personalized or secure content from other products participating in single
sign on.

Configuring this module to "Initialize this client as a proxy" allows
other modules on this site to make authenticated requests to other CAS
enabled products.

Configuring this module to "Allow this client to be proxied" lets the
specified sites use this site as a resource for portal channels or other
web services.


## Troubleshooting

The fastest way to determine why the module is not behaving as expected it to
enable the debug logging in this module's settings page. Messages related to
the authentication process, including errors, will be logged. To view these
logs, enable the Database Logging module or the Syslog module.


## API

### Events

Modules may subscribe to events to alter the behavior of the CAS module or
act on the data it provides.

All of the events that this module dispatches are located in the `src/Event`
folder. Please see the comments in each of those event classes for details about
what each event does and the common use cases for subscribing to them.

### Forcing authentication yourself

The CAS module will always attempt to authenticate a user when they visit the
/cas (or /caslogin) path, or if they visit a Forced Login or Gateway path that's
configured in the module's settings.

If there are other times you'd like to force a user to authenticate, use the
`CasRedirector` service. This service allows you to build a redirect response
object that will redirect users to the CAS server for authentication.

Inject this service class into one of your own services (like a kernel event
subscriber) and call the `buildRedirectResponse` method to create the response
object.

### Constructing a link that returns user to a specific page after login

It is often useful to provide a login link on your site that, when clicked,
will authenticate users via CAS and then return them to a specific page.

You can use the standard Drupal "destination" parameter to accomplish this:
`https://yoursite.com/caslogin?destination=/node/1`
