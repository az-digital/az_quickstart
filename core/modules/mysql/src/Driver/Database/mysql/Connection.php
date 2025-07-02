<?php

namespace Drupal\mysql\Driver\Database\mysql;

use Drupal\Core\Database\Connection as DatabaseConnection;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\DatabaseAccessDeniedException;
use Drupal\Core\Database\DatabaseConnectionRefusedException;
use Drupal\Core\Database\DatabaseException;
use Drupal\Core\Database\DatabaseNotFoundException;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Database\StatementWrapperIterator;
use Drupal\Core\Database\SupportsTemporaryTablesInterface;
use Drupal\Core\Database\Transaction\TransactionManagerInterface;

/**
 * @addtogroup database
 * @{
 */

/**
 * MySQL implementation of \Drupal\Core\Database\Connection.
 */
class Connection extends DatabaseConnection implements SupportsTemporaryTablesInterface {

  /**
   * Error code for "Unknown database" error.
   */
  const DATABASE_NOT_FOUND = 1049;

  /**
   * Error code for "Access denied" error.
   */
  const ACCESS_DENIED = 1045;

  /**
   * Error code for "Connection refused".
   */
  const CONNECTION_REFUSED = 2002;

  /**
   * {@inheritdoc}
   */
  protected $statementWrapperClass = StatementWrapperIterator::class;

  /**
   * Flag to indicate if the cleanup function in __destruct() should run.
   *
   * @var bool
   *
   * @deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. There's no
   *    replacement.
   *
   * @see https://www.drupal.org/node/3349345
   */
  protected $needsCleanup = FALSE;

  /**
   * Stores the server version after it has been retrieved from the database.
   *
   * @var string
   *
   * @see \Drupal\mysql\Driver\Database\mysql\Connection::version
   */
  private $serverVersion;

  /**
   * The minimal possible value for the max_allowed_packet setting of MySQL.
   *
   * @link https://mariadb.com/kb/en/mariadb/server-system-variables/#max_allowed_packet
   * @link https://dev.mysql.com/doc/refman/5.7/en/server-system-variables.html#sysvar_max_allowed_packet
   *
   * @var int
   */
  const MIN_MAX_ALLOWED_PACKET = 1024;

  /**
   * {@inheritdoc}
   */
  protected $identifierQuotes = ['"', '"'];

  /**
   * {@inheritdoc}
   */
  public function __construct(\PDO $connection, array $connection_options) {
    // If the SQL mode doesn't include 'ANSI_QUOTES' (explicitly or via a
    // combination mode), then MySQL doesn't interpret a double quote as an
    // identifier quote, in which case use the non-ANSI-standard backtick.
    //
    // Because we still support MySQL 5.7, check for the deprecated combination
    // modes as well.
    //
    // @see https://dev.mysql.com/doc/refman/5.7/en/sql-mode.html#sqlmode_ansi_quotes
    $ansi_quotes_modes = ['ANSI_QUOTES', 'ANSI', 'DB2', 'MAXDB', 'MSSQL', 'ORACLE', 'POSTGRESQL'];
    $is_ansi_quotes_mode = FALSE;
    if (isset($connection_options['init_commands']['sql_mode'])) {
      foreach ($ansi_quotes_modes as $mode) {
        // None of the modes in $ansi_quotes_modes are substrings of other modes
        // that are not in $ansi_quotes_modes, so a simple stripos() does not
        // return false positives.
        if (stripos($connection_options['init_commands']['sql_mode'], $mode) !== FALSE) {
          $is_ansi_quotes_mode = TRUE;
          break;
        }
      }
    }

    if ($this->identifierQuotes === ['"', '"'] && !$is_ansi_quotes_mode) {
      $this->identifierQuotes = ['`', '`'];
    }
    parent::__construct($connection, $connection_options);
  }

  /**
   * {@inheritdoc}
   */
  public static function open(array &$connection_options = []) {
    // The DSN should use either a socket or a host/port.
    if (isset($connection_options['unix_socket'])) {
      $dsn = 'mysql:unix_socket=' . $connection_options['unix_socket'];
    }
    else {
      // Default to TCP connection on port 3306.
      $dsn = 'mysql:host=' . $connection_options['host'] . ';port=' . (empty($connection_options['port']) ? 3306 : $connection_options['port']);
    }
    // Character set is added to dsn to ensure PDO uses the proper character
    // set when escaping. This has security implications. See
    // https://www.drupal.org/node/1201452 for further discussion.
    $dsn .= ';charset=utf8mb4';
    if (!empty($connection_options['database'])) {
      $dsn .= ';dbname=' . $connection_options['database'];
    }
    // Allow PDO options to be overridden.
    $connection_options += [
      'pdo' => [],
    ];
    $connection_options['pdo'] += [
      \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
      // So we don't have to mess around with cursors and unbuffered queries by default.
      \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => TRUE,
      // Make sure MySQL returns all matched rows on update queries including
      // rows that actually didn't have to be updated because the values didn't
      // change. This matches common behavior among other database systems.
      \PDO::MYSQL_ATTR_FOUND_ROWS => TRUE,
      // Because MySQL's prepared statements skip the query cache, because it's dumb.
      \PDO::ATTR_EMULATE_PREPARES => TRUE,
      // Limit SQL to a single statement like mysqli.
      \PDO::MYSQL_ATTR_MULTI_STATEMENTS => FALSE,
      // Convert numeric values to strings when fetching. In PHP 8.1,
      // \PDO::ATTR_EMULATE_PREPARES now behaves the same way as non emulated
      // prepares and returns integers. See https://externals.io/message/113294
      // for further discussion.
      \PDO::ATTR_STRINGIFY_FETCHES => TRUE,
    ];

    try {
      $pdo = new \PDO($dsn, $connection_options['username'], $connection_options['password'], $connection_options['pdo']);
    }
    catch (\PDOException $e) {
      switch ($e->getCode()) {
        case static::CONNECTION_REFUSED:
          if (isset($connection_options['unix_socket'])) {
            // Show message for socket connection via 'unix_socket' option.
            $message = 'Drupal is configured to connect to the database server via a socket, but the socket file could not be found.';
            $message .= ' This message normally means that there is no MySQL server running on the system or that you are using an incorrect Unix socket file name when trying to connect to the server.';
            throw new DatabaseConnectionRefusedException($e->getMessage() . ' [Tip: ' . $message . '] ', $e->getCode(), $e);
          }
          if (isset($connection_options['host']) && in_array(strtolower($connection_options['host']), ['', 'localhost'], TRUE)) {
            // Show message for socket connection via 'host' option.
            $message = 'Drupal was attempting to connect to the database server via a socket, but the socket file could not be found.';
            $message .= ' A Unix socket file is used if you do not specify a host name or if you specify the special host name localhost.';
            $message .= ' To connect via TCP/IP use an IP address (127.0.0.1 for IPv4) instead of "localhost".';
            $message .= ' This message normally means that there is no MySQL server running on the system or that you are using an incorrect Unix socket file name when trying to connect to the server.';
            throw new DatabaseConnectionRefusedException($e->getMessage() . ' [Tip: ' . $message . '] ', $e->getCode(), $e);
          }
          // Show message for TCP/IP connection.
          $message = 'This message normally means that there is no MySQL server running on the system or that you are using an incorrect host name or port number when trying to connect to the server.';
          $message .= ' You should also check that the TCP/IP port you are using has not been blocked by a firewall or port blocking service.';
          throw new DatabaseConnectionRefusedException($e->getMessage() . ' [Tip: ' . $message . '] ', $e->getCode(), $e);

        case static::DATABASE_NOT_FOUND:
          throw new DatabaseNotFoundException($e->getMessage(), $e->getCode(), $e);

        case static::ACCESS_DENIED:
          throw new DatabaseAccessDeniedException($e->getMessage(), $e->getCode(), $e);

        default:
          throw $e;
      }
    }

    // Force MySQL to use the UTF-8 character set. Also set the collation, if a
    // certain one has been set; otherwise, MySQL defaults to
    // 'utf8mb4_general_ci' (MySQL 5) or 'utf8mb4_0900_ai_ci' (MySQL 8) for
    // utf8mb4.
    if (!empty($connection_options['collation'])) {
      $pdo->exec('SET NAMES utf8mb4 COLLATE ' . $connection_options['collation']);
    }
    else {
      $pdo->exec('SET NAMES utf8mb4');
    }

    // Set MySQL init_commands if not already defined.  Default Drupal's MySQL
    // behavior to conform more closely to SQL standards.  This allows Drupal
    // to run almost seamlessly on many different kinds of database systems.
    // These settings force MySQL to behave the same as postgresql, or sqlite
    // in regards to syntax interpretation and invalid data handling.  See
    // https://www.drupal.org/node/344575 for further discussion. Also, as MySQL
    // 5.5 changed the meaning of TRADITIONAL we need to spell out the modes one
    // by one.
    $connection_options += [
      'init_commands' => [],
    ];

    $connection_options['init_commands'] += [
      'sql_mode' => "SET sql_mode = 'ANSI,TRADITIONAL'",
    ];
    if (!empty($connection_options['isolation_level'])) {
      $connection_options['init_commands'] += [
        'isolation_level' => 'SET SESSION TRANSACTION ISOLATION LEVEL ' . strtoupper($connection_options['isolation_level']),
      ];
    }

    // Execute initial commands.
    foreach ($connection_options['init_commands'] as $sql) {
      $pdo->exec($sql);
    }

    return $pdo;
  }

  /**
   * {@inheritdoc}
   */
  public function __destruct() {
    if ($this->needsCleanup) {
      $this->nextIdDelete();
    }
    parent::__destruct();
  }

  public function queryRange($query, $from, $count, array $args = [], array $options = []) {
    return $this->query($query . ' LIMIT ' . (int) $from . ', ' . (int) $count, $args, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function queryTemporary($query, array $args = [], array $options = []) {
    $tablename = 'db_temporary_' . uniqid();
    $this->query('CREATE TEMPORARY TABLE {' . $tablename . '} Engine=MEMORY ' . $query, $args, $options);
    return $tablename;
  }

  public function driver() {
    return 'mysql';
  }

  /**
   * {@inheritdoc}
   */
  public function version() {
    if ($this->isMariaDb()) {
      return $this->getMariaDbVersionMatch();
    }

    return $this->getServerVersion();
  }

  /**
   * Determines whether the MySQL distribution is MariaDB or not.
   *
   * @return bool
   *   Returns TRUE if the distribution is MariaDB, or FALSE if not.
   */
  public function isMariaDb(): bool {
    return (bool) $this->getMariaDbVersionMatch();
  }

  /**
   * Gets the MariaDB portion of the server version.
   *
   * @return string
   *   The MariaDB portion of the server version if present, or NULL if not.
   */
  protected function getMariaDbVersionMatch(): ?string {
    // MariaDB may prefix its version string with '5.5.5-', which should be
    // ignored.
    // @see https://github.com/MariaDB/server/blob/f6633bf058802ad7da8196d01fd19d75c53f7274/include/mysql_com.h#L42.
    $regex = '/^(?:5\.5\.5-)?(\d+\.\d+\.\d+.*-mariadb.*)/i';

    preg_match($regex, $this->getServerVersion(), $matches);
    return (empty($matches[1])) ? NULL : $matches[1];
  }

  /**
   * Gets the server version.
   *
   * @return string
   *   The PDO server version.
   */
  protected function getServerVersion(): string {
    if (!$this->serverVersion) {
      $this->serverVersion = $this->query('SELECT VERSION()')->fetchField();
    }
    return $this->serverVersion;
  }

  public function databaseType() {
    return 'mysql';
  }

  /**
   * Overrides \Drupal\Core\Database\Connection::createDatabase().
   *
   * @param string $database
   *   The name of the database to create.
   *
   * @throws \Drupal\Core\Database\DatabaseNotFoundException
   */
  public function createDatabase($database) {
    // Escape the database name.
    $database = Database::getConnection()->escapeDatabase($database);

    try {
      // Create the database and set it as active.
      $this->connection->exec("CREATE DATABASE $database");
      $this->connection->exec("USE $database");
    }
    catch (\Exception $e) {
      throw new DatabaseNotFoundException($e->getMessage());
    }
  }

  public function mapConditionOperator($operator) {
    // We don't want to override any of the defaults.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function nextId($existing_id = 0) {
    @trigger_error('Drupal\Core\Database\Connection::nextId() is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Modules should use instead the keyvalue storage for the last used id. See https://www.drupal.org/node/3349345', E_USER_DEPRECATED);
    $this->query('INSERT INTO {sequences} () VALUES ()');
    $new_id = $this->lastInsertId();
    // This should only happen after an import or similar event.
    if ($existing_id >= $new_id) {
      // If we INSERT a value manually into the sequences table, on the next
      // INSERT, MySQL will generate a larger value. However, there is no way
      // of knowing whether this value already exists in the table. MySQL
      // provides an INSERT IGNORE which would work, but that can mask problems
      // other than duplicate keys. Instead, we use INSERT ... ON DUPLICATE KEY
      // UPDATE in such a way that the UPDATE does not do anything. This way,
      // duplicate keys do not generate errors but everything else does.
      $this->query('INSERT INTO {sequences} (value) VALUES (:value) ON DUPLICATE KEY UPDATE value = value', [':value' => $existing_id]);
      $this->query('INSERT INTO {sequences} () VALUES ()');
      $new_id = $this->lastInsertId();
    }
    $this->needsCleanup = TRUE;
    return $new_id;
  }

  public function nextIdDelete() {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Modules should use instead the keyvalue storage for the last used id. See https://www.drupal.org/node/3349345', E_USER_DEPRECATED);
    // While we want to clean up the table to keep it up from occupying too
    // much storage and memory, we must keep the highest value in the table
    // because InnoDB uses an in-memory auto-increment counter as long as the
    // server runs. When the server is stopped and restarted, InnoDB
    // re-initializes the counter for each table for the first INSERT to the
    // table based solely on values from the table so deleting all values would
    // be a problem in this case. Also, TRUNCATE resets the auto increment
    // counter.
    try {
      $max_id = $this->query('SELECT MAX(value) FROM {sequences}')->fetchField();
      // We know we are using MySQL here, no need for the slower ::delete().
      $this->query('DELETE FROM {sequences} WHERE value < :value', [':value' => $max_id]);
    }
    // During testing, this function is called from shutdown with the
    // simpletest prefix stored in $this->connection, and those tables are gone
    // by the time shutdown is called so we need to ignore the database
    // errors. There is no problem with completely ignoring errors here: if
    // these queries fail, the sequence will work just fine, just use a bit
    // more database storage and memory.
    catch (DatabaseException $e) {
    }
  }

  /**
   * {@inheritdoc}
   */
  public function exceptionHandler() {
    return new ExceptionHandler();
  }

  /**
   * {@inheritdoc}
   */
  public function select($table, $alias = NULL, array $options = []) {
    return new Select($this, $table, $alias, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function insert($table, array $options = []) {
    return new Insert($this, $table, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function merge($table, array $options = []) {
    return new Merge($this, $table, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function upsert($table, array $options = []) {
    return new Upsert($this, $table, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function update($table, array $options = []) {
    return new Update($this, $table, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function delete($table, array $options = []) {
    return new Delete($this, $table, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function truncate($table, array $options = []) {
    return new Truncate($this, $table, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function schema() {
    if (empty($this->schema)) {
      $this->schema = new Schema($this);
    }
    return $this->schema;
  }

  /**
   * {@inheritdoc}
   */
  public function condition($conjunction) {
    return new Condition($conjunction);
  }

  /**
   * {@inheritdoc}
   */
  protected function driverTransactionManager(): TransactionManagerInterface {
    return new TransactionManager($this);
  }

  /**
   * {@inheritdoc}
   */
  public function startTransaction($name = '') {
    return $this->transactionManager()->push($name);
  }

}


/**
 * @} End of "addtogroup database".
 */
