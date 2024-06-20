use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class DynamicForm extends FormBase {
  public function getFormId() {
    return 'dynamic_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['dynamic_elements'] = [
      '#type' => 'container',
      '#prefix' => '<div id="dynamic-elements-wrapper">',
      '#suffix' => '</div>',
    ];

    $dynamic_elements = $form_state->get('dynamic_elements') ?: [];
    foreach ($dynamic_elements as $key => $element) {
      $form['dynamic_elements'][$key] = $element;
    }

    $form['add_element'] = [
      '#type' => 'button',
      '#value' => $this->t('Add Element'),
      '#ajax' => [
        'callback' => '::addElementCallback',
        'wrapper' => 'dynamic-elements-wrapper',
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  public function addElementCallback(array &$form, FormStateInterface $form_state) {
    $dynamic_elements = $form_state->get('dynamic_elements') ?: [];
    $dynamic_elements[] = [
      '#type' => 'textfield',
      '#title' => $this->t('Dynamic Element'),
      '#name' => 'dynamic_element_' . count($dynamic_elements),
    ];
    $form_state->set('dynamic_elements', $dynamic_elements);

    $form['dynamic_elements'] = [
      '#type' => 'container',
      '#prefix' => '<div id="dynamic-elements-wrapper">',
      '#suffix' => '</div>',
    ];

    foreach ($dynamic_elements as $key => $element) {
      $form['dynamic_elements'][$key] = $element;
    }

    return $form['dynamic_elements'];
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $dynamic_elements = $form_state->get('dynamic_elements') ?: [];
    $submitted_values = [];

    foreach ($dynamic_elements as $key => $element) {
      $submitted_values[] = $form_state->getValue($element['#name']);
    }

    // Process the submitted values as needed
    // For example, you could save them to the database
    \Drupal::messenger()->addMessage(t('Submitted values: @values', ['@values' => implode(', ', $submitted_values)]));
  }
}
