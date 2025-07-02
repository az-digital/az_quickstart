<?php

namespace Drupal\Core\Session;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\DatabaseException;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Storage\Proxy\AbstractProxy;

/**
 * Default session handler.
 */
class SessionHandler extends AbstractProxy implements \SessionHandlerInterface {

  use DependencySerializationTrait;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new SessionHandler instance.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Component\Datetime\TimeInterface|null $time
   *   The time service.
   */
  public function __construct(RequestStack $request_stack, Connection $connection, protected ?TimeInterface $time = NULL) {
    $this->requestStack = $request_stack;
    $this->connection = $connection;
    if (!$time) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $time argument is deprecated in drupal:10.3.0 and it will be required in drupal:11.0.0. See https://www.drupal.org/node/3387233', E_USER_DEPRECATED);
      $this->time = \Drupal::service(TimeInterface::class);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function open(string $save_path, string $name): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function read(#[\SensitiveParameter] string $sid): string|false {
    $data = '';
    if (!empty($sid)) {
      try {
        // Read the session data from the database.
        $query = $this->connection
          ->queryRange('SELECT [session] FROM {sessions} WHERE [sid] = :sid', 0, 1, [':sid' => Crypt::hashBase64($sid)]);
        $data = (string) $query->fetchField();
      }
      // Swallow the error if the table hasn't been created yet.
      catch (\Exception) {
      }
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function write(#[\SensitiveParameter] string $sid, string $value): bool {
    $try_again = FALSE;
    $request = $this->requestStack->getCurrentRequest();
    $fields = [
      'uid' => $request->getSession()->get('uid', 0),
      'hostname' => $request->getClientIP(),
      'session' => $value,
      'timestamp' => $this->time->getRequestTime(),
    ];
    $doWrite = fn() =>
      $this->connection->merge('sessions')
        ->keys(['sid' => Crypt::hashBase64($sid)])
        ->fields($fields)
        ->execute();
    try {
      $doWrite();
    }
    catch (\Exception $e) {
      // If there was an exception, try to create the table.
      if (!$try_again = $this->ensureTableExists()) {
        // If the exception happened for other reason than the missing
        // table, propagate the exception.
        throw $e;
      }
    }
    // Now that the bin has been created, try again if necessary.
    if ($try_again) {
      $doWrite();
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function close(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function destroy(#[\SensitiveParameter] string $sid): bool {
    try {
      // Delete session data.
      $this->connection->delete('sessions')
        ->condition('sid', Crypt::hashBase64($sid))
        ->execute();
    }
    // Swallow the error if the table hasn't been created yet.
    catch (\Exception) {
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function gc(int $lifetime): int|false {
    // Be sure to adjust 'php_value session.gc_maxlifetime' to a large enough
    // value. For example, if you want user sessions to stay in your database
    // for three weeks before deleting them, you need to set gc_maxlifetime
    // to '1814400'. At that value, only after a user doesn't log in after
    // three weeks (1814400 seconds) will their session be removed.
    try {
      return $this->connection->delete('sessions')
        ->condition('timestamp', $this->time->getRequestTime() - $lifetime, '<')
        ->execute();
    }
    // Swallow the error if the table hasn't been created yet.
    catch (\Exception) {
    }
    return FALSE;
  }

  /**
   * Defines the schema for the session table.
   *
   * @internal
   */
  protected function schemaDefinition(): array {
    $schema = [
      'description' => "Drupal's session handlers read and write into the sessions table. Each record represents a user session, either anonymous or authenticated.",
      'fields' => [
        'uid' => [
          'description' => 'The {users}.uid corresponding to a session, or 0 for anonymous user.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ],
        'sid' => [
          'description' => "A session ID (hashed). The value is generated by Drupal's session handlers.",
          'type' => 'varchar_ascii',
          'length' => 128,
          'not null' => TRUE,
        ],
        'hostname' => [
          'description' => 'The IP address that last used this session ID (sid).',
          'type' => 'varchar_ascii',
          'length' => 128,
          'not null' => TRUE,
          'default' => '',
        ],
        'timestamp' => [
          'description' => 'The Unix timestamp when this session last requested a page. Old records are purged by PHP automatically.',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'size' => 'big',
        ],
        'session' => [
          'description' => 'The serialized contents of the user\'s session, an array of name/value pairs that persists across page requests by this session ID. Drupal loads the user\'s session from here   at the start of each request and saves it at the end.',
          'type' => 'blob',
          'not null' => FALSE,
          'size' => 'big',
        ],
      ],
      'primary key' => [
        'sid',
      ],
      'indexes' => [
        'timestamp' => ['timestamp'],
        'uid' => ['uid'],
      ],
      'foreign keys' => [
        'session_user' => [
          'table' => 'users',
          'columns' => ['uid' => 'uid'],
        ],
      ],
    ];

    return $schema;
  }

  /**
   * Check if the session table exists and create it if not.
   *
   * @return bool
   *   TRUE if the table already exists or was created, FALSE if creation fails.
   */
  protected function ensureTableExists(): bool {
    try {
      $database_schema = $this->connection->schema();
      $schema_definition = $this->schemaDefinition();
      $database_schema->createTable('sessions', $schema_definition);
    }
    // If another process has already created the table, attempting to create
    // it will throw an exception. In this case just catch the exception and do
    // nothing.
    catch (DatabaseException $e) {
    }
    catch (\Exception $e) {
      return FALSE;
    }
    return TRUE;
  }

}
