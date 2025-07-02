<?php

namespace Drupal\webform\Plugin\Action;

use Drupal\Core\Action\Plugin\Action\DeleteAction;

/**
 * Redirects to a webform submission deletion form.
 *
 * @Action(
 *   id = "webform_submission_delete_action",
 *   label = @Translation("Delete submission"),
 *   type = "webform_submission",
 *   confirm_form_route_name = "webform_submission.multiple_delete_confirm"
 * )
 */
class WebformSubmissionDeleteAction extends DeleteAction {}
