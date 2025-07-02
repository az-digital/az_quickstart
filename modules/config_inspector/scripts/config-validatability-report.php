<?php

/**
 * @file
 * Script to analyze the last core commit of every day since a start date.
 */

declare(strict_types=1);

// phpcs:disable
// To run this repeatedly while developing: git tag | grep 10.99 | xargs -n 1 git tag -d && php config-validatability-report.php
// phpcs:enable

$branch = '11.x';

$utc = new DateTimeZone("UTC");
$start = new DateTime($argv[1] ?? "2023-01-01 00:00:00", $utc);
$now = new DateTimeImmutable("now", $utc);
$interval = $now->diff($start);
print sprintf("🚀 Analyzing \e[1;37;44m%d days\e[0m (from %s to %s)…\n",
  $interval->days,
  $start->format("Y-m-d H:i:s P"),
  $now->format("Y-m-d H:i:s P"),
);
$rev_original = @shell_exec('git rev-list -n 1 HEAD');
$rev_previous = $result_previous = NULL;

$statistics = fopen('statistics.csv', 'w');
fputcsv($statistics, [
  'date',
  'when',
  'overall',
  'objectPropertyPathsValidatable',
  'objectPropertyPathsFullyValidatable',
  'objectsImplicitlyFullyValidatable',
  'objectsFullyValidatable',
  'typesInUsePartiallyValidatable',
  'typesInUseImplicitlyFullyValidatable',
  'typesInUseFullyValidatable',
  'typesFullyValidatable',
]);

$index = 1;
$day = $start;
do {
  // Gather all info about this day.
  $when = $day->format("Y-m-d H:i:s P");
  $date = $day->format("Y-m-d");
  $rev_current = @shell_exec("git rev-list -n 1 --before='$when' origin/$branch");
  $rev_current_info = @shell_exec("git log --oneline -n 1 $rev_current");

  // Jump to next day.
  $day->modify('+24 hours');

  // Analyze this day.
  if ($rev_current == $rev_previous) {
    $assessment = $assessment_previous;
  }
  else {
    $assessment = assess_revision($rev_current, $index++, $date);
  }
  $result = compute_result($assessment);
  $progress = match(TRUE) {
    $result_previous === NULL => 'same',
    $result > $result_previous => 'better',
    $result < $result_previous => 'worse',
    default => 'same',
  };
  foreach ($assessment->prep_output as $prep_output_line) {
    print $prep_output_line;
  }
  print sprintf("🕰️  \e[1;37;44m%s\e[0m → %s%03.2f%%\e[0m %s\n",
    $when,
    match($progress) {
      'same' => "\e[1;37;40m",
      'better' => "\e[1;37;42m",
      'worse' => "\e[1;37;41m",
    },
    $result,
    $rev_current == $rev_previous
      ? "⏩ No commits this day."
      // Limit each line to 200 columns.
      : '(' . mb_strimwidth(rtrim($rev_current_info), 0, 154, '…') . ')',
  );
  fputcsv($statistics, [
    $date,
    $when,
    $result,
    $assessment->objectPropertyPathsValidatable,
    $assessment->objectPropertyPathsFullyValidatable,
    $assessment->objectsImplicitlyFullyValidatable,
    $assessment->objectsFullyValidatable,
    $assessment->typesInUsePartiallyValidatable,
    $assessment->typesInUseImplicitlyFullyValidatable,
    $assessment->typesInUseFullyValidatable,
    $assessment->typesFullyValidatable,
  ]);

  // Prepare for tomorrow.
  $rev_previous = $rev_current;
  $result_previous = $result;
  $assessment_previous = $assessment;
} while ($day < $now);

print "📊 Analysis complete. See statistics.csv (raw data in statistics/*.json).\n";
fclose($statistics);

/**
 * Computes a detailed assessment for the given revision.
 *
 * @param string $revision
 *   A revision, which must be newer than the previously assessed revision.
 * @param int $day
 *   A monotonically increasing integer (representing the Nth day).
 * @param string $date
 *   A `Y-m-d` string: a calendar date representation corresponding to $day.
 *
 * @return \stdClass
 *   A detailed assessment.
 */
function assess_revision(string $revision, int $day, string $date): \stdClass {
  static $installed;
  $prep_output = [];
  // Forceful checkout because the composer.lock file was modified due to
  // requiring Drush.
  // TRICKY: checkouts result in detached states, which in turn results in
  // Composer not being able to deduce a version.
  $dbg_git_checkout = shell_exec("git reset --hard --quiet $revision");
  // TRICKY:
  // 1. Work around composer deciding we're at version 1.0.0 (if we do a
  //    checkout of the rev)
  // 2. Branches require more I/O (slower).
  // 3. So pretend there is a Drupal 10.99 minor, and tag a "patch release" per
  //    day.
  // 4. This also aids in debugging: if the script crashes, just do
  //    `git diff TAG1 TAG2`.
  @shell_exec("git tag 10.99.$day");
  $prev = $day > 1 ? $day - 1 : $day;
  $standard_profile_config_changed = @shell_exec("git diff 10.99.$prev 10.99.$day --name-only | grep 'core/profiles/standard/config\|config/optional'");
  if (!empty($standard_profile_config_changed)) {
    print "🤖                               Drupal's standard install profile config changed, uninstalling…\n";
    @shell_exec("rm -rf sites/default/files sites/default/settings.php");
    unset($installed);
  }
  $was_just_installed = FALSE;
  if (!isset($installed)) {
    print "🤖                               Installing Drupal's standard install profile…\n";
    @shell_exec("composer require drush/drush --quiet");
    @shell_exec("php core/scripts/drupal install standard --quiet");
    @shell_exec("vendor/bin/drush pm:install config_inspector --yes --quiet");
    $installed = TRUE;
    $was_just_installed = TRUE;
  }
  if (!$was_just_installed) {
    // Composer install if lock file changed, and reinstall drush.
    $prep_output[] = @shell_exec("git diff 10.99.$prev 10.99.$day --name-only | grep -q composer\.lock && echo '🤖                               Reinstalling Composer packages (including Drush) because composer.lock has changed…' && composer require drush/drush --quiet");
    // Ensure `drush config:inspect --statistics` keeps working.
    $prep_output[] = @shell_exec("git diff 10.99.$prev 10.99.$day --name-only | grep -q '.install$\|.post_update\.php$' && echo '🤖                               Installing DB updates…' && vendor/bin/drush updatedb --yes --quiet");
    $prep_output[] = @shell_exec("git diff 10.99.$prev 10.99.$day --name-only | grep -q '.schema\.yml$' && echo '🤖                               Erasing discovery cache because config schema changed…' && vendor/bin/drush cc bin discovery --quiet");
    $prep_output[] = @shell_exec("git diff 10.99.$prev 10.99.$day --name-only | grep -q '^core\/lib\/Drupal\/Core\/Config\/Schema\/' && echo '🤖                               Erasing discovery cache because config schema infrastructure changed…' && vendor/bin/drush cc bin discovery --quiet");
    $prep_output[] = @shell_exec("git diff 10.99.$prev 10.99.$day --name-only | grep -q '\/Validation\/' && echo '🤖                               Rebuilding container because validation constraints were added or modified…' && vendor/bin/drush cr --quiet");
    $prep_output[] = @shell_exec("git diff 10.99.$prev 10.99.$day --name-only | grep -q '^core\/lib\/Drupal\/Core\/.*Kernel' && echo '🤖                               Rebuilding container because kernel infrastructure changed…' && vendor/bin/drush cr --quiet");
    $prep_output[] = @shell_exec("git diff 10.99.$prev 10.99.$day --name-only | grep -q '.services\.yml$' && echo '🤖                               Rebuilding container because services changed…' && vendor/bin/drush cr --quiet");
  }
  // Actually gather statistics.
  @shell_exec("vendor/bin/drush config:inspect --statistics > statistics/$date.json");
  $assessment_json = @shell_exec("jq -r .assessment statistics/$date.json");
  $assessment = json_decode($assessment_json);
  $assessment->prep_output = $prep_output;
  return $assessment;
}

/**
 * Computes a single percentage "result" based on the detailed assessment.
 *
 * @param \stdClass $assessment
 *   A detailed assessment.
 *
 * @return float
 *   A percentage, >=0 and <=100.
 */
function compute_result(\stdClass $assessment): float {
  // ⚠️ When this reaches 100%, it only means the types in use in THIS INSTALL
  // are fully validatable!
  return (
    // 50% of the impact: types in use fully validatable?
    (
      // The percentage that is ready.
      $assessment->objectPropertyPathsFullyValidatable
      +
      // Assume that the percentage that is partially ready is only half ready.
      ($assessment->objectPropertyPathsValidatable - $assessment->objectPropertyPathsFullyValidatable) * 0.5
    )
    +
    // The other 50% of the impact: object property paths fully validatable?
    (
      // The percentage that is ready.
      $assessment->typesInUseFullyValidatable
      +
      // Assume that the percentage that is partially ready is only 1/3rd ready.
      ($assessment->typesInUsePartiallyValidatable - $assessment->typesInUseFullyValidatable) * 0.33
    )
  )
    // The above summed two percentages that both must reach 100%.
    / 2
    // Convert to number in the [0, 100] range.
    * 100;
}
