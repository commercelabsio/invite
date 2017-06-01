<?php

namespace Drupal\invite_by_email\Plugin\Invite;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\invite\InvitePluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class for Invite by Email.
 *
 * @Plugin(
 *   id="invite_by_email",
 *   label = @Translation("Invite By Email")
 * )
 */
class InviteByEmail implements InvitePluginInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function send($invite) {
    /*
     * @var $token \Drupal\token\Token
     * @var $mail \Drupal\Core\Mail\MailManager
     */
    $bubbleable_metadata = new BubbleableMetadata();
    $token = \Drupal::service('token');
    $mail = \Drupal::service('plugin.manager.mail');
    $mail_key = $invite->get('type')->value;
    // Prepare message.
    $message = $mail->mail('invite_by_email', $mail_key, $invite->get('field_invite_email_address')->value, $invite->activeLangcode, [], $invite->getOwner()
      ->getEmail(), FALSE);
    // If HTML email.
    if (unserialize(\Drupal::config('invite.invite_type.' . $invite->get('type')->value)
      ->get('data'))['html_email']
    ) {
      $message['headers']['Content-Type'] = 'text/html; charset=UTF-8;';
    }
    $message['subject'] = $token->replace($invite->get('field_invite_email_subject')->value, ['invite' => $invite], [], $bubbleable_metadata);
    $body = [
      '#theme' => 'invite_by_email',
      '#body' => $token->replace($invite->get('field_invite_email_body')->value, ['invite' => $invite], [], $bubbleable_metadata),
    ];
    $message['body'] = \Drupal::service('renderer')
      ->render($body)
      ->__toString();
    // Send.
    $system = $mail->getInstance([
      'module' => 'invite_by_email',
      'key' => $mail_key,
    ]);

    $result = $system->mail($message);

    if ($result) {
      drupal_set_message($this->t('Invitation has been sent.'));
    }
    else {
      drupal_set_message($this->t('Failed to send a message.'), 'error');
    }

  }

}
