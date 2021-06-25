<?php

namespace DrupalCodeBuilder\Definition;

use MutableTypedData\Exception\InvalidDefinitionException;

/**
 * Defines a data property with properties loaded lazily from a generator.
 *
 * This allows us to avoid circularity and errors with generators that need to
 * remove properties from their parent class's property definition.
 *
 * For example:
 *  - The TestModule generator needs to remove the unit tests property,
 *    otherwise its definition will be circular, leading through unit tests to
 *    test module again.
 *  - The Module7 generator needs to remove properties for generators that don't
 *    exist for Drupal 7.
 *
 * TODO: added in haste to update Module generator to use data definitions -
 * fold this into the parent?
 */
class LazyGeneratorDefinition extends GeneratorDefinition {

  /**
   * Creates a new definition from a component type and data type.
   *
   * TODO: this should become the only way to get a definition from a generator.
   * Rename after clean-up!
   * See Plugin class for new plan!
   *
   * @param string $generator_type
   *   The generator type; that is, the short class name without the version
   *   number.
   * @param string $data_type
   *   The data type.
   *
   * @return static
   */
  public static function createFromGeneratorTypeWithSetProperties(string $generator_type, string $data_type): self {
    $definition = new static($data_type, $generator_type);

    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  public function getProperties() {
    if (empty($this->componentType)) {
      throw new InvalidDefinitionException("Call to getProperties() when no component type has been set.");
    }

    // Only lazy-load properties for a complex or mutable definition.
    if (empty($this->properties) && in_array($this->type, ['complex', 'mutable'])) {
      $class_handler = \DrupalCodeBuilder\Factory::getTask('Generate\ComponentClassHandler');

      // Pass this definition to the generator and let it add the properties.
      $generator_class = $class_handler->getGeneratorClass($this->componentType);

      if (!class_exists($generator_class)) {
        throw new InvalidDefinitionException("Class $generator_class not found for $this->componentType.");
      }

      $generator_class::setProperties($this);
    }

    return $this->properties;
  }

}
