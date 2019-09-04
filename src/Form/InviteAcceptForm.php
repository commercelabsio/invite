<?php


namespace Drupal\invite\Form;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\invite\Controller\InviteAccept;
use Drupal\invite\InviteInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class InviteAcceptForm extends FormBase {

  /**
   * The invite storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $invite_storage;

  /**
   * The invite accept controller.
   *
   * @var \Drupal\invite\Controller\InviteAccept
   */
  protected $invite_accept;

  /**
   * Constructs a InviteAcceptForm object.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $invite_storage
   *   Invite storage.
   * @param \Drupal\invite\Controller\InviteAccept
   *   The invite accept controller.
   */
  public function __construct(EntityStorageInterface $invite_storage, InviteAccept $invite_accept) {
    $this->invite_storage = $invite_storage;
    $this->invite_accept = $invite_accept;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_type_manager = $container->get('entity_type.manager');
    return new static(
      $entity_type_manager->getStorage('invite'),
      InviteAccept::create($container)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'invite_accept_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, InviteInterface $invite = NULL) {
    $form['reg_code'] = array(
      '#type' => 'textfield',
      '#title' => t('Registration Code'),
      '#size' => 10,
      '#maxlength' => 10,
      '#required' => TRUE,
    );

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Claim'),
      '#button_type' => 'primary',
    );
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!$this->invite_storage->loadByProperties(['reg_code' => $form_state->getValue('reg_code')])) {
      $form_state->setErrorByName('reg_code', $this->t('This is not a valid code.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $invite = $this->invite_storage->loadByProperties(['reg_code' => $form_state->getValue('reg_code')]);

    $redirect_response = $this->invite_accept->accept(reset($invite));

    $form_state->setResponse($redirect_response);
  }

}
