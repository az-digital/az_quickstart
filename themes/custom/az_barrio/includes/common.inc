<?php

/**
 * @file
 * common.inc
 *
 * Contains common functionality for the entire theme.
 * @deprecated in 2.8.0 and is removed from 2.9.0. Use modules/custom/az_core/includes/common.inc instead.
 */

//phpcs:ignore Security.BadFunctions.EasyRFI.WarnEasyRFI
require_once \Drupal::service('extension.list.module')->getPath('az_core') . '/includes/common.inc';
