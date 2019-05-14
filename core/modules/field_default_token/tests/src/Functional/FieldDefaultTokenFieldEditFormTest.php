<?php

namespace Drupal\Tests\field_default_token\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests that the field edit form displays an entered token correctly.
 *
 * @group field_default_token
 */
class FieldDefaultTokenFieldEditFormTest extends BrowserTestBase {

  /**
   * The ID of the entity type used in the test.
   *
   * @var string
   */
  protected $entityTypeId = 'entity_test';

  /**
   * The name of the field used in the test.
   *
   * @var string
   */
  protected $fieldName = 'field_text';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity_test', 'field_default_token', 'field_ui'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => $this->entityTypeId,
      'type' => 'string',
    ])->save();
    FieldConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => $this->entityTypeId,
      'bundle' => $this->entityTypeId,
    ])->setDefaultValue('This is the site name: [site:name]')->save();

    $account = $this->drupalCreateUser(['administer entity_test fields']);
    $this->drupalLogin($account);
  }

  /**
   * Tests that tokens are not replaced in the field configuration edit form.
   */
  public function testTokenDisplay() {
    $this->drupalGet("/entity_test/structure/entity_test/fields/{$this->entityTypeId}.{$this->entityTypeId}.{$this->fieldName}");
    $this->assertSession()->statusCodeEquals(200);
    $value = $this->assertSession()->fieldExists($this->fieldName)->getValue();
    $this->assertEquals('This is the site name: [site:name]', $value);
  }

}
