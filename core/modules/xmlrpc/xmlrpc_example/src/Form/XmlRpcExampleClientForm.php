<?php

/**
 * @file
 * Contains \Drupal\xmlrpc_example\Form\XmlRpcExampleClientForm.
 *
 * This is the client part of the module. If defines a form with two input
 * fields to call xmlrpc_example.add or xmlrpc_example.subtract methods on this
 * host. Please note that having a user interface to query an XML-RPC service is
 * not required. A method can be requested to a server using the xmlrpc()
 * function directly. We have included an user interface to make the testing
 * easier.
 */

namespace Drupal\xmlrpc_example\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\xmlrpc\XmlRpcTrait;

/**
 * Form demonstrating XML-RPC client.
 *
 * Presents a two arguments form and makes a call to an XML-RPC server using
 * these arguments as input, showing the result in a message.
 */
class XmlRpcExampleClientForm extends FormBase {

  use XmlRpcTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'xmlrpc_example_client';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('xmlrpc_example.server');

    $form['explanation'] = array(
      '#markup' => '<div>' . $this->t('This example demonstrates how to make XML-RPC calls with Drupal. <br />The "Request methods" button makes a request to the server and asks for the available list of methods, as a service discovery request. <br/>The "Add integers" and "Subtract integers" use the xmlrpc() function to act as a client, calling the XML-RPC server defined in this same example for some defined methods.<br />An XML-RPC error will result if the result in the addition or subtraction requested is out of bounds defined by the server. These error numbers are defined by the server. <br />The "Add and Subtract" button performs a multicall operation on the XML-RPC server: several requests in a single XML-RPC call.<br />') . '</div>',
    );
    // We are going to call add and subtract methods, and
    // they work with integer values.
    $form['num1'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Enter an integer'),
      '#default_value' => 2,
      '#size' => 5,
      '#required' => TRUE,
    );
    $form['num2'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Enter a second integer'),
      '#default_value' => 2,
      '#size' => 5,
      '#required' => TRUE,
    );
    // Include several buttons, each of them calling a different method.
    // This button submits a XML-RPC call to the system.listMethods method.
    $form['information'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Request methods'),
      '#submit' => array(array($this, 'submitInformation')),
    );
    // This button submits a XML-RPC call to the xmlrpc_example.add method.
    $form['add'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Add the integers'),
      '#submit' => array(array($this, 'submitAdd')),
    );
    // This button submits a XML-RPC call to the xmlrpc_example.subtract method.
    $form['subtract'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Subtract the integers'),
      '#submit' => array(array($this, 'submitSubstract')),
    );
    // This button submits a XML-RPC call to the system.multicall method.
    $form['add_subtract'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Add and Subtract'),
      '#submit' => array(array($this, 'submitAddSubstract')),
    );

    if ($config->get('alter_enabled')) {
      $form['overridden'] = array(
        '#type' => 'markup',
        '#markup' => '<div><strong>' . $this->t('Just a note of warning: The <a href=":url">alter form</a> has been used to disable the limits, so you may want to turn that off if you do not want it.', array(
          ':url' => Url::fromRoute('xmlrpc_example.alter')->toString(),
        )) . '</strong></div>',
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Submit handler to query system.listMethods.
   *
   * Submit: query the XML-RPC endpoint for the method system.listMethods
   * and report the result as a Drupal message. The result is a list of the
   * available methods in this XML-RPC server.
   *
   * Important note: Not all XML-RPC servers implement this method. Drupal's
   * built-in XML-RPC server implements this method by default.
   *
   * @param array $form
   *   Form array.
   * @param FormStateInterface $form_state
   *   Form_state object.
   *
   * @see xmlrpc()
   * @see xmlrpc_errno()
   * @see xmlrpc_error_msg()
   */
  public function submitInformation(array &$form, FormStateInterface $form_state) {
    // First define the endpoint of the XML-RPC service, in this case this is
    // our own server.
    $server = $this->getEndpoint();
    // Then we should define the method to call. xmlrpc() requires that all the
    // information related to the called method be passed as an array in the
    // form of 'method_name' => arguments_array
    $options = array(
      'system.listMethods' => array(),
    );
    // Make the xmlrpc request and process the results.
    $result = xmlrpc($server, $options);
    if ($result === FALSE) {
      drupal_set_message(
        $this->t('Error return from xmlrpc(): Error: @errno, Message: @message',
          array('@errno' => xmlrpc_errno(), '@message' => xmlrpc_error_msg())),
        'error'
      );
    }
    else {
      drupal_set_message(
        $this->t('The XML-RPC server returned this response: <pre>@response</pre>',
          array('@response' => print_r($result, TRUE)))
      );
    }
  }

  /**
   * Submit handler to query xmlrpc_example.add.
   *
   * Submit: query the XML-RPC endpoint for the method xmlrpc_example.add
   * and report the result as a Drupal message.
   *
   * @param array $form
   *   Form array.
   * @param FormStateInterface $form_state
   *   Form_state object.
   *
   * @see xmlrpc()
   * @see xmlrpc_errno()
   * @see xmlrpc_error_msg()
   */
  public function submitAdd(array &$form, FormStateInterface $form_state) {
    // First define the endpoint of the XML-RPC service. In this case this is
    // our own server.
    $server = $this->getEndpoint();
    // Then we should define the method to call. xmlrpc() requires that all the
    // information related to the called method is passed as an array in the
    // form of 'method_name' => arguments_array
    $options = array(
      'xmlrpc_example.add' => array(
        (int) $form_state->getValue('num1'),
        (int) $form_state->getValue('num2'),
      ),
    );
    // Make the xmlrpc request and process the results.
    $result = xmlrpc($server, $options);
    if ($result === FALSE) {
      drupal_set_message(
        $this->t('Error return from xmlrpc(): Error: @errno, Message: @message', array(
          '@errno' => xmlrpc_errno(),
          '@message' => xmlrpc_error_msg(),
        )),
        'error'
      );
    }
    else {
      drupal_set_message(
        $this->t('The XML-RPC server returned this response: @response', array(
          '@response' => print_r($result, TRUE),
        ))
      );
    }
  }

  /**
   * Submit handler to query xmlrpc_example.subtract.
   *
   * Submit: query the XML-RPC endpoint for the method xmlrpc_example.subtract
   * and report the result as a Drupal message.
   *
   * @param array $form
   *   Form array.
   * @param FormStateInterface $form_state
   *   Form_state object.
   *
   * @see xmlrpc()
   * @see xmlrpc_errno()
   * @see xmlrpc_error_msg()
   * @see xmlrpc_example_client_add_submit()
   */
  public function submitSubstract(array &$form, FormStateInterface $form_state) {
    $server = $this->getEndpoint();
    $options = array(
      'xmlrpc_example.subtract' => array(
        (int) $form_state->getValue('num1'),
        (int) $form_state->getValue('num2'),
      ),
    );
    // Make the xmlrpc request and process the results.
    $result = xmlrpc($server, $options);
    if ($result === FALSE) {
      drupal_set_message(
        $this->t('Error return from xmlrpc(): Error: @errno, Message: @message',
           array('@errno' => xmlrpc_errno(), '@message' => xmlrpc_error_msg())),
        'error'
      );
    }
    else {
      drupal_set_message(
        $this->t('The XML-RPC server returned this response: @response',
          array('@response' => print_r($result, TRUE)))
      );
    }
  }

  /**
   * Submit a multicall request.
   *
   * Submit a multicall request: query the XML-RPC endpoint for the methods
   * xmlrpc_example.add and xmlrpc_example.subtract and report the result as a
   * Drupal message. Drupal's XML-RPC client builds the system.multicall request
   * automatically when there is more than one method to call.
   *
   * @param array $form
   *   Form array.
   * @param FormStateInterface $form_state
   *   Form_state object.
   *
   * @see xmlrpc()
   * @see xmlrpc_errno()
   * @see xmlrpc_error_msg()
   * @see xmlrpc_example_client_multicall_submit()
   */
  public function submitAddSubstract(array &$form, FormStateInterface $form_state) {
    $server = $this->getEndpoint();

    /*
     * The XML-RPC server built in the xmlrpc module supports system.multicall.
     *
     * To make a multicall request, the main invoked method should be the
     * function 'system.multicall', and the arguments to make this call must be
     * defined as an array of single method calls, being the array keys the
     * service methods to be called, and the array elements being the method
     * arguments.
     *
     * See the code below this comment as example.
     */

    // Build an array of several calls, The built-in XML-RPC support will
    // construct the correct system.multicall request for the server.
    $options = array(
      'xmlrpc_example.add' => array(
        (int) $form_state->getValue('num1'),
        (int) $form_state->getValue('num2'),
      ),
      'xmlrpc_example.subtract' => array(
        (int) $form_state->getValue('num1'),
        (int) $form_state->getValue('num2'),
      ),
    );
    // Make the XML-RPC request and process the results.
    $result = xmlrpc($server, $options);

    if ($result === FALSE) {
      drupal_set_message(
        $this->t('Error return from xmlrpc(): Error: @errno, Message: @message', array(
          '@errno' => xmlrpc_errno(),
          '@message' => xmlrpc_error_msg(),
      )));
    }
    else {
      drupal_set_message(
        $this->t('The XML-RPC server returned this response: <pre>@response</pre>', array(
          '@response' => print_r($result, TRUE),
      )));
    }
  }

}
