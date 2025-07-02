SMTP Authentication Support module for Drupal 8.x.
This module adds SMTP functionality to Drupal.

REQUIREMENTS
------------

* Access to an SMTP server that will accept mail from you.
* The following PHP extensions need to be installed: hash, date & pcre.
* The PHPMailer class has been unbundled, and must be installed with composer.
  Refer to https://github.com/PHPMailer/PHPMailer for details.

* Optional: To connect to an SMTP server using SSL, you need to have the
  openssl package installed on your server, and your webserver and PHP
  installation need to have additional components installed and configured.

INSTALLATION INSTRUCTIONS
-------------------------

1.  Install this module using Composer. Doing so will also install the PHPMailer dependency.
    If for some reason the PHPMailer dependency/library did not get installed (for example if you had a
    conflicting PHPMailer library installed, or you opted to manually install this module rather than using Composer), install PHPMailer separately with Composer, as follows:
    `composer require phpmailer/phpmailer`
    `composer require phpmailer/phpmailer:6.1.7`
2.  Enable the module:
    a.  Login as site administrator, visit the Extend page, and enable SMTP.
    b.  Run "drush pm-enable smtp" on the command line.
3.  Enable the SMTP Authentication Support module on the Manage -> Extend
    page.
4.  Fill in required settings on the Manage -> Configuration -> System ->
    SMTP Authentication Support page.
5.  Enjoy.

CONFIGURATION
------------
* Configure the user permissions in Administration -> People -> Permissions:

    - Administer SMTP Authentication Support module.

RELATED MODULES
---------------

You may find the following modules helpful:

 - mailsystem: controls which mail-related modules are used by other actions.
 - mimemail: Makes HTML email easier to send.
 - pet: Previewable Templating module
 - rules: can send emails when "events" happen, such as publishing a node.

NOTES
-----

Valid SMTP Server

  This module sends email by connecting to an SMTP server.  Therefore, you need
  to have access to an SMTP server, that is configured to accept email from you,
  for this module to work.

Selecting Connection Port

  Mail servers listen for new mail on TCP/IP numbered "ports", and while these
  can be whatever the administrator determines, the standard values are:

    + 25  : Internet-wide email transmission by default in cleartext but can
            upgrade to TLS when both sides support it;
    + 465 : A version of port 25 for which SSL is enabled from the beginning.
            Now deprecated in favour of port 587 and the use of "STARTTLS";
    + 587 : "Mail submission" - used for clients talking to a "smarthost"
            server (e.g. the ISP server) for queueing and/or forwarding.

  The recommendation is for SMTP module to use port 587 with STARTTLS for
  secure (especially cross-internet) connections to the ISP email server,
  and port 25 for insecure and/or local connections. However, the actual port
  and server to use will depend on the email server you will be using, and
  if the above does not work for you, you should refer to that server's
  documentation for more help.

  Finally, it is worth mentioning that the popular development tool "mailhog"
  uses port "8025".

Setting the From email address:

  Drupal normally uses the From email address (see Manage -> Configuration ->
  Site information -> E-mail address) as the Mail From address. It is important
  for this to be the correct address and many ISPs will block email that comes
  from an address they do not recognise as "theirs".

  Sending mail to GMail requires SSL or TLS. Connecting to an SMTP server using
  SSL is possible only if PHP's openssl extension is working.  If the SMTP
  module detects openssl is available it will display the options in the SMTP
  settings page. Alternatively, run on the web host:

      php -i | grep "Stream Socket Transports"

  and look for at least one of:

      ssl, tls, tlsv1.1, tlsv1.2

  in the output.

Google Mail Authentication:

  GMail currently supports using OAuth2 to authenticate senders of email, to
  ensure that only the valid owners can use it. OAuth2 will be required from
  early 2021 and new accounts will require it from mid-2020.

  This restriction means that GMail and GSuite email addresses cannot be used
  without also enabling OAuth2, which is not currently supported by the module.


----
Note for Office365 users:

The 'E-mail from name' field will most likley be ignored by Office365-hosted
Exchange. Please see this Microsoft KB article for more details:
https://support.microsoft.com/en-us/help/4458479/improvements-in-smtp-authenticated-submission-client-protocol
