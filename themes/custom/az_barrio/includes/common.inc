<?php

/**
 * @file
 * common.inc
 *
 * Contains common functionality for the entire theme.
 * @deprecated Use modules/custom/az_core/includes/common.inc instead.
 */

//phpcs:ignore Security.BadFunctions.EasyRFI.WarnEasyRFI
require_once \Drupal::service('extension.list.module')->getPath('az_core') . '/includes/common.inc';
