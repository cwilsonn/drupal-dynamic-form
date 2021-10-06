<?php

namespace Drupal\dynamic_form\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\MessageCommand;

/**
 * Provides a dynamic form with conditional field rendering logic via AJAX.
 */
class DynamicForm extends FormBase
{
  /**
   * Provides an associative array of framework content type node titles keyed by ID.
   *
   * @return array
   *   An associative array of framework content type node titles keyed by ID
   */
  public function getFrameworkOptions()
  {
    // Empty array to return our framework node options.
    $frameworkOptions = [];
    // Query and load framework content nodes.
    $entity = \Drupal::entityTypeManager()->getStorage('node');
    $query = $entity->getQuery();
    $ids = $query->condition('status', 1)
      ->condition('type', 'framework')
      ->sort('title', 'DESC')
      ->execute();
    $nodes = $entity->loadMultiple($ids);
    // Add node data and 'other' option to $frameworkOptions[].
    foreach ($nodes as $node) {
      $frameworkOptions[$node->id()] = $node->label();
    }
    $frameworkOptions['other'] = $this->t('Other');

    return $frameworkOptions;
  }

  /**
   * Provides functionality to rebuild our form and to render the $form['other']['framework'] field in buildForm().
   *
   * @return \Drupal\Core\Render\Element\RenderElement
   */
  public function handleFrameworkSelectedFieldChange(array &$form, FormStateInterface $form_state)
  {
    $form_state->setRebuild(TRUE);
    if ($form_state->getValue('other')) {
      return $form['other'];
    } else {
      return $form;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $form['#tree'] = TRUE;
    $form['#title'] = $this->t('Frontend Framework Survey');
    $form['#prefix'] = '<div id="conditional-field-ajax-form">';
    $form['#suffix'] = '</div>';

    $form['description'] = [
      '#markup' => $this->t('This form uses AJAX callbacks to render a conditional field, per requirements. See <a href="/dynamic-form/form-demo-using-state">this page</a> for an example using an alternative method, the form #states property.'),
    ];

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#placeholder' => $this->t('Please enter your name'),
      '#required' => TRUE,
    ];

    $form['framework'] = [
      '#type' => 'select',
      '#title' => $this->t('Favorite framework'),
      '#options' => $this->getFrameworkOptions(),
      '#ajax' => [
        'callback' => '::handleFrameworkSelectedFieldChange',
        'event' => 'change',
        'wrapper' => 'conditional-field-ajax-form',
      ],
    ];

    if ($form_state->getValue('framework') === 'other' && $form_state->getValue('other') === NULL) {
      $form['other'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Other Framework'),
        '#placeholder' => $this->t('Please enter a framework'),
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'button',
      '#value' => $this->t('Submit'),
      '#ajax' => [
        'callback' => '::handleAjaxSubmit'
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'dynamic_form_dynamic_form';
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    return;
  }

  /**
   * AJAX form submission handler
   */
  public function handleAjaxSubmit(array &$form, FormStateInterface $form_state)
  {
    $name = $form_state->getValue('name');
    $selected_framework = $form_state->getValue('framework');

    if ($selected_framework === 'other') {
      // $framework = $form_state->getValue(['other', 'framework']);
      $framework = $form_state->getValue('other');
    } else {
      $framework = $form['framework']['#options'][$selected_framework];
    }

    $response = new AjaxResponse();
    $response->addCommand(new MessageCommand(
      $this->t(
        'Your name is %name and your favorite framework is %framework',
        ['%name' => $name, '%framework' => $framework]
      )
    ));

    return $response;
  }
}