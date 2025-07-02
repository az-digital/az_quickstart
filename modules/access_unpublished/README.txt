ACCESS UNPUBLISHED 

Description:
------------
Module grants access to view unpublished content to anyone who has 
a unique URL and appropriate permissions. Visitor can be anonymous 
or authenticated user with the user role.

When administrator or users with enabled privileges visit (or save) 
unpublished node, can see link for direct view that unpublished content. 
Unique URL link is displayed as Drupal message or in node content.
   
Administrator can enable view an unpublished node for any roles with 
"View unpublished contents" permission. If it is set for anonymous users, 
anyone who know the link with hash key, can view the unpublished node. 
View only, not edit. 

Default URL parameter is "hash" and can be changed on configuration page 
for more security or customization.

Module is useful for proofreaders, content checkers etc. 
Webmaster does not need to create user accounts and can keep the website safer. 
Each node has its own unique hash key (like Google Docs).


Usage:
------
After installing and activating the 'Access unpublished' module you should 
first configure the settings for the module.
  admin/config/content/access_unpublished
You can change the value of the 'URL hash parameter'. This is the value used 
in the URL to identify the generated hash.

Afterwards you will need to set the module permissions
  admin/people/permissions


Authors:
-------
Access unpublished module was created by 
aberg (http://drupal.org/user/341657 http://leiden365.nl/blog)
for Drupal 6, where module development was stopped. Then martin_klima started
independent module development for Drupal 7 named Hash access as sandbox 
project. During review process Drupal team recommended build Hash access as 7.x
development branch of Access unpublished. 
