<?php

/**
 * @file
 * Definition of ModuleBuilder\Generator\RouterItem.
 */

namespace ModuleBuilder\Generator;

/**
 * Generator for a router item.
 *
 * This class covers Drupal 6 and 7, where it is purely an intermediary which
 * adds a HookMenu component.
 *
 * @see RouterItem8
 */
class RouterItem extends BaseGenerator {

  /**
   * The unique name of this generator.
   *
   * A generator's name is used as the key in the $components array.
   *
   * A RouterItem generator should use as its name its path.
   *
   * TODO: at what point do names start to clash and we need prefixes based on
   * type???!!
   */
  public $name;

  /**
   * Constructor method; sets the component data.
   *
   * @param $component_name
   *   The identifier for the component.
   * @param $component_data
   *   (optional) An array of data for the component. Any missing properties
   *   (or all if this is entirely omitted) are given default values.
   *   Valid properties are:
   *      - 'title': The title for the item.
   *      - TODO: further properties such as access!
   */
  function __construct($component_name, $component_data, $generate_task, $root_generator) {
    // Set some default properties.
    // This allows the user to leave off specifying details like title and
    // access, and get default strings in place that they can replace in
    // generated module code.
    $component_data += array(
      // Use a default that can be selected with a single double-click, to make
      // it easy to replace.
      'title' => 'myPage',
    );

    parent::__construct($component_name, $component_data, $generate_task, $root_generator);
  }

  /**
   * @inheritdoc
   */
  public static function requestedComponentHandling() {
    return 'repeat';
  }

  /**
   * Declares the subcomponents for this component.
   *
   * @return
   *  An array of subcomponent names and types.
   */
  protected function requiredComponents() {
    // Create the item data for the HookMenu component.
    $menu_item = array(
      'path' => $this->name,
    );

    // Copy properties from the RouterItem component data to the hook_menu()
    // item.
    $properties_to_copy = array(
      'title',
      'description',
      'page callback',
      'page arguments',
      'access callback',
      'access arguments',
      'file',
    );
    foreach ($properties_to_copy as $property_name) {
      if (isset($this->component_data[$property_name])) {
        $menu_item[$property_name] = $this->component_data[$property_name];
      }
    }

    $return = array(
      // Each RouterItem that gets added will cause a repeat request of these
      // components.
      'hook_menu' => array(
        'component_type' => 'HookMenu',
        // This is a numeric array of items, so repeated requests of this
        // component will merge it.
        'menu_items' => array(
          $menu_item,
        ),
      ),
    );

    return $return;
  }

}
