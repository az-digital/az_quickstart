<?php

namespace Drupal\az_migration\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Drupal 7 Person node source plugin.
 *
 * @MigrateSource(
 *   id = "az_person"
 * )
 */
class Person extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // This queries the built-in metadata, but not the body, tags, or images.
    $query = $this->select('node', 'n')
      ->condition('n.type', 'uaqs_person')
      ->fields('n', [
        'nid',
        'vid',
        'type',
        'language',
        'title',
        'uid',
        'status',
        'created',
        'changed',
        'promote',
        'sticky',
      ]);
    $query->orderBy('nid');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = $this->baseFields();
    $fields['body/format'] = $this->t('Format of body');
    $fields['body/value'] = $this->t('Full text of body');
    $fields['body/summary'] = $this->t('Summary of body');
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {

    // Get the source nid.
    $nid = $row->getSourceProperty('nid');

    // Setting Person Category.
    $result = $this->getDatabase()->query('
      SELECT
        GROUP_CONCAT(pc.field_uaqs_person_category_tid) as tids
      FROM
        {field_data_field_uaqs_person_category} pc
      WHERE
        pc.entity_id = :nid
    ', [':nid' => $nid]);

    foreach ($result as $record) {
      if (!is_null($record->tids)) {
        $row->setSourceProperty('person_category', explode(',', $record->tids));
      }
    }

    // Setting Person Category Secondary.
    $result = $this->getDatabase()->query('
      SELECT
        GROUP_CONCAT(psc.field_uaqs_person_categories_tid) as tids
      FROM
        {field_data_field_uaqs_person_categories} psc
      WHERE
        psc.entity_id = :nid
      ', [':nid' => $nid]);
    foreach ($result as $record) {
      if (!is_null($record->tids)) {
        $row->setSourceProperty('person_category_secondary', explode(',', $record->tids));
      }
    }

    // Setting Person First Name.
    $result = $this->getDatabase()->query('
      SELECT
        pfn.field_uaqs_fname_value,
        pfn.field_uaqs_fname_format
      FROM
        {field_data_field_uaqs_fname} pfn
      WHERE
        pfn.entity_id = :nid
      ', [':nid' => $nid]);
    foreach ($result as $record) {
      if (!is_null($record->field_uaqs_fname_value)) {
        $row->setSourceProperty('person_fname_value', $record->field_uaqs_fname_value);
        $row->setSourceProperty('person_fname_format', $record->field_uaqs_fname_format);
      }
    }

    // Setting Person Last Name.
    $result = $this->getDatabase()->query('
      SELECT
        pln.field_uaqs_lname_value,
        pln.field_uaqs_lname_format
      FROM
        {field_data_field_uaqs_lname} pln
      WHERE
        pln.entity_id = :nid
      ', [':nid' => $nid]);
    foreach ($result as $record) {
      if (!is_null($record->field_uaqs_lname_value)) {
        $row->setSourceProperty('person_lname_value', $record->field_uaqs_lname_value);
        $row->setSourceProperty('person_lname_format', $record->field_uaqs_lname_format);
      }
    }

    // Setting Person Email.
    $result = $this->getDatabase()->query('
      SELECT
        pe.field_uaqs_email_email
      FROM
        {field_data_field_uaqs_email} pe
      WHERE
        pe.entity_id = :nid
      ', [':nid' => $nid]);
    foreach ($result as $record) {
      if (!is_null($record->field_uaqs_email_email)) {
        $row->setSourceProperty('person_email', $record->field_uaqs_email_email);
      }
    }

    // Setting Person Job Title.
    $person_job_title = [];
    $result = $this->getDatabase()->query('
      SELECT
        pjt.field_uaqs_titles_value,
        pjt.field_uaqs_titles_format,
        pjt.delta
      FROM
        {field_data_field_uaqs_titles} pjt
      WHERE
        pjt.entity_id = :nid
      ', [':nid' => $nid]);
    foreach ($result as $record) {
      if (!is_null($record->field_uaqs_titles_value)) {
        $person_job_title[] = [
          'delta' => $record->delta,
          'value' => $record->field_uaqs_titles_value,
          'format' => $record->field_uaqs_titles_format,
        ];
      }
    }
    $row->setSourceProperty('person_job_title', $person_job_title);

    // Setting Person Degrees.
    $person_degrees = [];
    $result = $this->getDatabase()->query('
      SELECT
        pdg.field_uaqs_degrees_value,
        pdg.field_uaqs_degrees_format,
        pdg.delta
      FROM
        {field_data_field_uaqs_degrees} pdg
      WHERE
        pdg.entity_id = :nid
      ', [':nid' => $nid]);
    foreach ($result as $record) {
      if (!is_null($record->field_uaqs_degrees_value)) {
        $person_degrees[] = [
          'delta' => $record->delta,
          'value' => $record->field_uaqs_degrees_value,
          'format' => $record->field_uaqs_degrees_format,
        ];
      }
    }
    $row->setSourceProperty('person_degrees', $person_degrees);

    // Setting Person Phones.
    $person_phones = [];
    $result = $this->getDatabase()->query('
      SELECT
        pph.field_uaqs_phones_value,
        pph.delta
      FROM
        {field_data_field_uaqs_phones} pph
      WHERE
        pph.entity_id = :nid
      ', [':nid' => $nid]);
    foreach ($result as $record) {
      if (!is_null($record->field_uaqs_phones_value)) {
        $person_phones[] = [
          'delta' => $record->delta,
          'value' => $record->field_uaqs_phones_value,
        ];
      }
    }
    $row->setSourceProperty('person_phones', $person_phones);

    // Setting Person links.
    $person_links = [];
    $result = $this->getDatabase()->query('
      SELECT
        plk.field_uaqs_links_url,
        plk.field_uaqs_links_title,
        plk.field_uaqs_links_attributes,
        plk.delta
      FROM
        {field_data_field_uaqs_links} plk
      WHERE
        plk.entity_id = :nid
      ', [':nid' => $nid]);
    foreach ($result as $record) {
      if (!is_null($record->field_uaqs_links_url)) {
        $person_links[] = [
          'delta' => $record->delta,
          'url' => $record->field_uaqs_links_url,
          'title' => $record->field_uaqs_links_title,
          'attributes' => $record->field_uaqs_links_attributes,
        ];
      }
    }
    $row->setSourceProperty('person_links', $person_links);

    // Setting Person Addresses.
    $person_addresses = [];
    $result = $this->getDatabase()->query('
      SELECT
        pad.field_uaqs_addresses_value,
        pad.field_uaqs_addresses_summary,
        pad.field_uaqs_addresses_format,
        pad.delta
      FROM
        {field_data_field_uaqs_addresses} pad
      WHERE
        pad.entity_id = :nid
      ', [':nid' => $nid]);
    foreach ($result as $record) {
      if (!is_null($record->field_uaqs_addresses_value)) {
        $person_addresses[] = [
          'delta' => $record->delta,
          'value' => $record->field_uaqs_addresses_value,
          'summary' => $record->field_uaqs_addresses_summary,
          'format' => $record->field_uaqs_addresses_format,
        ];
      }
    }
    $row->setSourceProperty('person_addresses', $person_addresses);

    // Setting Person Biography.
    $person_biography = [];
    $result = $this->getDatabase()->query('
    SELECT
      pbio.field_uaqs_bio_value,
      pbio.field_uaqs_bio_summary,
      pbio.field_uaqs_bio_format,
      pbio.delta
    FROM
      {field_data_field_uaqs_bio} pbio
    WHERE
      pbio.entity_id = :nid
    ', [':nid' => $nid]);
    foreach ($result as $record) {
      if (!is_null($record->field_uaqs_bio_value)) {
        $person_biography[] = [
          'delta' => $record->delta,
          'value' => $record->field_uaqs_bio_value,
          'summary' => $record->field_uaqs_bio_summary,
          'format' => $record->field_uaqs_bio_format,
        ];
      }
    }
    $row->setSourceProperty('person_biography', $person_biography);

    // Setting Person CV/Documents.
    $person_attachment = [];
    $result = $this->getDatabase()->query('
      SELECT
        pat.field_uaqs_cv_documents_fid,
        pat.field_uaqs_cv_documents_display,
        pat.field_uaqs_cv_documents_description,
        pat.delta
      FROM
        {field_data_field_uaqs_cv_documents} pat
      WHERE
        pat.entity_id = :nid
      ', [':nid' => $nid]);
    foreach ($result as $record) {
      if (!is_null($record->field_uaqs_cv_documents_fid)) {
        $person_attachment[] = [
          'delta' => $record->delta,
          'fid' => $record->field_uaqs_cv_documents_fid,
          'display' => $record->field_uaqs_cv_documents_display,
          'description' => $record->field_uaqs_cv_documents_description,
        ];
      }
    }
    $row->setSourceProperty('person_attachment', $person_attachment);

    // Setting Person Photos.
    $person_photo = [];
    $result = $this->getDatabase()->query("
      SELECT
        uapt.field_uaqs_photo_fid,
        uapt.field_uaqs_photo_alt,
        uapt.field_uaqs_photo_title,
        uapt.field_uaqs_photo_width,
        uapt.field_uaqs_photo_height,
        uapt.delta
      FROM
        {field_data_field_uaqs_photo} uapt
      WHERE
        uapt.entity_id = :nid
        AND
        uapt.bundle = 'uaqs_person'
      ", [':nid' => $nid]);
    foreach ($result as $record) {
      if (!is_null($record->field_uaqs_photo_fid)) {
        $person_photo[] = [
          'delta' => $record->delta,
          'fid' => $record->field_uaqs_photo_fid,
          'alt' => $record->field_uaqs_photo_alt,
          'title' => $record->field_uaqs_photo_title,
          'width' => $record->field_uaqs_photo_width,
          'height' => $record->field_uaqs_photo_height,
        ];
      }
    }
    $row->setSourceProperty('person_photo', $person_photo);

    // Setting Person Url Alias.
    $row->setSourceProperty('pathauto', 1);
    $person_attachment = [];
    $result = $this->getDatabase()->query("
      SELECT
        pa.alias
      FROM
        {url_alias} pa
      WHERE
        pa.source = :source
      ", [':source' => 'node/' . $nid]);
    foreach ($result as $record) {
      if (!is_null($record->alias)) {
        $row->setSourceProperty('path', $record->alias);
        $row->setSourceProperty('pathauto', 0);
      }
    }

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['nid']['type'] = 'integer';
    $ids['nid']['alias'] = 'n';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function bundleMigrationRequired() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function entityTypeId() {
    return 'node';
  }

  /**
   * Returns the user base fields to be migrated.
   *
   * @return array
   *   Associative array having field name as key and description as value.
   */
  protected function baseFields() {
    $fields = [
      'nid' => $this->t('Node ID'),
      'vid' => $this->t('Version ID'),
      'type' => $this->t('Type'),
      'title' => $this->t('Title'),
      'format' => $this->t('Format'),
      'teaser' => $this->t('Teaser'),
      'uid' => $this->t('Authored by (uid)'),
      'created' => $this->t('Created timestamp'),
      'changed' => $this->t('Modified timestamp'),
      'status' => $this->t('Published'),
      'promote' => $this->t('Promoted to front page'),
      'sticky' => $this->t('Sticky at top of lists'),
      'language' => $this->t('Language (fr, en, ...)'),
    ];
    return $fields;
  }

}
