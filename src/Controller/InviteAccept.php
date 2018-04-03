<?php

namespace Drupal\invite\Controller;

use Drupal\invite\InviteConstants;
use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Core\Controller\ControllerBase;
use Drupal\invite\InviteAcceptEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class InviteAccept.
 *
 * @package Drupal\invite\Controller
 */
class InviteAccept extends ControllerBase {

  public $dispatcher;

  /**
   * Functin for create.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('event_dispatcher')
    );
  }

  /**
   * Construct.
   */
  public function __construct(ContainerAwareEventDispatcher $dispatcher) {
    $this->dispatcher = $dispatcher;
  }

  /**
   * Accepts an invitation.
   */
  public function accept($invite) {
    $account = $this->currentUser();
    $redirect = '<front>';
    $message = 'Hmm.';
    $type = 'status';

    // Current user is the inviter.
    if ($account->id() == $invite->getOwnerId()) {
      $message = $this->t("You can't use your own invite.");
      $type = 'error';
    }
    // Invite has already been used.
    elseif ($invite->getStatus() == InviteConstants::INVITE_USED) {
      $message = $this->t('Sorry, this invitation has already been used.');
      $type = 'error';
    }

    // Invite has already been withdrawn.
    elseif ($invite->getStatus() == InviteConstants::INVITE_WITHDRAWN) {
      $message = $this->t('Sorry, this invitation has already been withdrawn.');
      $type = 'error';
    }

    // Invite is expired.
    elseif ($invite->expires->value < time()) {
      $message = $this->t('Sorry, this invitation is expired.');
      $type = 'error';
      $invite->setStatus(InviteConstants::INVITE_EXPIRED);
      $invite->save();
    }

    // Good to go!
    else {
      $_SESSION['invite_code'] = $invite->getRegCode();
      $redirect = 'user.register';
      $message = $this->t('Please create an account to accept the invitation.');
    }

    // Let other modules act on the invite accepting before the user is created.
    $invite_accept = new InviteAcceptEvent([
      'redirect' => &$redirect,
      'message' => &$message,
      'type' => &$type,
      'invite' => &$invite,
    ]);

    $this->dispatcher->dispatch('invite_accept', $invite_accept);
    drupal_set_message($message, $type);

    return $this->redirect($redirect);
  }

}
