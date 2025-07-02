# Masquerade Log

This module extends [Masquerade](https://www.drupal.org/project/masquerade) by
logging also the original user in all logger entries when the current user is
masquerading.

Normally the log entry user ID is the current user. But when a user is
masquerading we cannot determine the original user from the log entry. By
enabling this module, a suffix will be added to the log entry message. For
example, if the original username is `joe`, with user ID equals `1234`, and they
are masquerading as `anna`, the log entry user ID will still belong to `anna`,
as currently happens, but the log message will end with:

```
[masquerading joe, uid 1234]
```

Loggers that are storing also variables, such as DbLog, will receive also two
additional context variables:

- `@original_uid`
- `@original_username`

This module needs no configuration.
