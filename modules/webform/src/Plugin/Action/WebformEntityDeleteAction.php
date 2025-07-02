<?php

namespace Drupal\webform\Plugin\Action;

use Drupal\Core\Action\Plugin\Action\DeleteAction;

/**
 * Redirects to a webform deletion form.
 *
 * @Action(
 *   id = "webform_delete_action",
 *   label = @Translation("Delete webform"),
 *   type = "webform",
 *   confirm_form_route_name = "entity.webform.multiple_delete_confirm"
 * )
 */
class WebformEntityDeleteAction extends DeleteAction {}
