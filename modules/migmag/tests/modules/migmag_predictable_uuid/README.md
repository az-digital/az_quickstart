# Migrate Magician Predictable UUID

Migrate Magician Predictable UUID provides flexible, predictable UUIDs in tests.
Each time a UUID is being generated, the generator checks whether it should
return a predictable UUID or not.

## Configuration options

The generator watches PredictableUuid::WATCHED_CLASSES_STATE_KEY, which should
be the list of the fully qualified class names or PHP file paths, grouped and
keyed by the desired UUID prefix or the UUID template. This state should be set
before you want to get predictable UUIDs.

```
\Drupal::state()->set(
  PredictableUuid::WATCHED_CLASSES_STATE_KEY,
  [
    'content-uuid-' => [ContentEntityStorageBase::class],
    'system-uuid-' => ['core/modules/system/system.install'],
    'every-other-' => [get_class($this)],
  ]
);
```

## Usage example

See MigmagPredictableUuidTest::testUuidGeneratorWithNode().

## Limitations

The generator can only check the backtrace from the point where the generator
was called. This means that it cannot generate predictable UUIDs only for node
entities, because neither the Node class, nor NodeStorage calls the UUID
generator: it is called in the storage class' base class
ContentEntityStorageBase.
