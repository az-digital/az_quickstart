/**
 * @file
 * Message template overrides.
 */

((Drupal) => {
  /**
   * Overrides message theme function.
   *
   * @param {object} message
   *   The message object.
   * @param {string} message.text
   *   The message text.
   * @param {object} options
   *   The message context.
   * @param {string} options.type
   *   The message type.
   * @param {string} options.id
   *   ID of the message, for reference.
   *
   * @return {HTMLElement}
   *   A DOM Node.
   */
  Drupal.theme.message = ({ text }, { type, id }) => {
    const messagesTypes = Drupal.Message.getMessageTypeLabels();
    const messagesBootstrapTypes = {
      status: 'success',
      warning: 'warning',
      error: 'danger',
      info: 'info',
    };
    const messageWrapper = document.createElement('div');

    messageWrapper.setAttribute(
      'class',
      `messages messages--${type}`
    );
    messageWrapper.setAttribute(
      'role',
      type === 'error' || type === 'warning' ? 'alert' : 'status'
    );
    messageWrapper.setAttribute('data-drupal-message-id', id);
    messageWrapper.setAttribute('data-drupal-message-type', type);

    messageWrapper.innerHTML = `
    <div class="messages__content container alert alert-${messagesBootstrapTypes[type]} alert-dismissible fade show" role="alert">
      <h2 class="visually-hidden">
        ${messagesTypes[type]}
      </h2>
      <span class="messages__item">
        ${text}
      </span>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
    </div>
  `;

    return messageWrapper;
  };
})(Drupal);
