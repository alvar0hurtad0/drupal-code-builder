<?php

namespace DrupalCodeBuilder\Task\Generate;

use DrupalCodeBuilder\Environment\EnvironmentInterface;
use DrupalCodeBuilder\Generator\RootComponent;

/**
 * Task helper for preparing a component's property info for use by UIs.
 */
class ComponentPropertyPreparer {

  /**
   * Prepares a property in the component data with default value and options.
   */
  public function prepareComponentDataProperty($property_name, &$property_info, &$component_data) {
    // Set options.
    $this->prepareComponentDataPropertyCreateOptions($property_name, $property_info);
    if (isset($property_info['properties'])) {
      foreach ($property_info['properties'] as $child_property_name => &$child_property_info) {
        $this->prepareComponentDataPropertyCreateOptions($child_property_name, $child_property_info);
      }
    }
    // Argh, deal with PHP's wacky behaviour with refereced loop variables.
    unset($child_property_info);

    // Set a default value.
    $this->setComponentDataPropertyDefault($property_name, $property_info, $component_data);
    // Handle compound property child items if they are present.
    if (isset($property_info['properties']) && isset($component_data[$property_name]) && is_array($component_data[$property_name])) {
      foreach ($component_data[$property_name] as $delta => $delta_data) {
        foreach ($property_info['properties'] as $child_property_name => $child_property_info) {
          $this->setComponentDataPropertyDefault($child_property_name, $child_property_info, $component_data[$property_name][$delta]);
        }
      }
    }
  }

  /**
   * Helper to create the options array for a property.
   *
   * This calls the property info's options callback and replaces it with the
   * resulting array of options.
   *
   * @param $property_name
   *  The name of the property.
   * @param &$property_info
   *  The property into array, passed by reference.
   */
  protected function prepareComponentDataPropertyCreateOptions($property_name, &$property_info) {
    if (isset($property_info['options'])) {
      $options_callback = $property_info['options'];
      // We may have prepared this options array from a callback previously,
      // when preparing multiple child items of a compound property.
      if (!is_callable($options_callback)) {
        return;
      }
      $options = $options_callback($property_info);

      $property_info['options'] = $options;
    }
  }

  /**
   * Set the default value for a property in component data.
   *
   * @param $property_name
   *  The name of the property. For child properties, this is the name of just
   *  the child property.
   * @param $property_info
   *  The property info array for the property.
   * @param &$component_data_local
   *  The array of component data, or for child properties, the item array that
   *  immediately contains the property. In other words, this array would have
   *  a key $property_name if data has been supplied for this property.
   */
  protected function setComponentDataPropertyDefault($property_name, $property_info, &$component_data_local) {
    // Don't clobber a default value that's already been set. This can happen
    // if a parent property sets a default value for a child item.
    // TODO: consider whether the child item should win if it has a default
    // value or callback of its own -- or indeed if this combination ever
    // happens.
    if (isset($component_data_local[$property_name])) {
      return;
    }

    // During the prepare stage, we always want to provide a default, for the
    // convenience of the user in the UI.
    if (isset($property_info['default'])) {
      if (is_callable($property_info['default'])) {
        $default_callback = $property_info['default'];
        $default_value = $default_callback($component_data_local);
      }
      else {
        $default_value = $property_info['default'];
      }
      $component_data_local[$property_name] = $default_value;
    }
    else {
      // In the prepare stage, always set the property name, even if it's
      // something basically empty.
      // (This allows UIs to rely on this and set it as their default no
      // matter what.)
      $default_value = $property_info['format'] == 'array' ? array() : NULL;
      $component_data_local[$property_name] = $default_value;
    }
  }

}
