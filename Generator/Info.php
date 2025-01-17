<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\File\DrupalExtension;

/**
 * Generator class for module info file for Drupal 10 and higher.
 */
class Info extends InfoBase {

  /**
   * {@inheritdoc}
   */
  public static function getPropertyDefinition(): PropertyDefinition {
    $definition = parent::getPropertyDefinition();

    $definition->addProperty(PropertyDefinition::create('string')
      ->setName('base')
      ->setAutoAcquiredFromRequester()
    );

    $definition->getProperty('filename')
      ->setLiteralDefault('%module.info.yml');

    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  public function detectExistence(DrupalExtension $extension) {
    $info_filename = $this->getFilename();

    // Info files don't necessarily exist in the case of test modules.
    $this->exists = $extension->hasFile($info_filename);

    if ($this->exists) {
      $yaml = $extension->getFileYaml($info_filename);
      // No idea of format here! Probably unique for each generator!
      // For info files, the only thing which is mergeable
      $this->existing = $yaml;
    }
  }

  /**
   * Build the code files.
   */
  public function getFileInfo() {
    $file = parent::getFileInfo();

    $file['filename'] = '%module.info.yml';

    return $file;
  }

  /**
   * Create lines of file body for Drupal 8 and higher.
   */
  function infoData(): array {
    $module_data = $this->component_data;
    // dump("FILE BODY");
    // dump($module_data->export());

    $lines = $this->getInfoFileEmptyLines();
    $lines['name'] = $module_data->readable_name->value;
    $lines['type'] = $module_data->base->value;
    $lines['description'] = $module_data->short_description->value;
    // For lines which form a set with the same key and array markers,
    // simply make an array.
    foreach ($module_data->module_dependencies as $dependency) {
      $lines['dependencies'][] = $dependency->value;
    }

    if ($module_data->hasProperty('lifecycle') && $module_data->lifecycle->value) {
      $lines['lifecycle'] = $module_data->lifecycle->value;
    }

    if (!$module_data->module_package->isEmpty()) {
      $lines['package'] = $module_data->module_package->value;
    }

    // TODO: Move this to a helper method.
    $lines['core_version_requirement'] = '^8 || ^9 || ^10';

    if (!empty($extra_lines = $this->getContainedComponentInfoLines())) {
      $lines = array_merge($lines, $extra_lines);
    }
    // dump($lines);

    return $lines;
  }

  /**
   * Process a structured array of info files lines to a flat array for merging.
   *
   * @param $lines
   *  An array of lines keyed by label.
   *  Place grouped labels (eg, dependencies) into an array of
   *  their own, keyed numerically.
   *  Eg:
   *    name => module name
   *    dependencies => array(foo, bar)
   *
   * @return
   *  An array of lines for the .info file.
   */
  function process_info_lines($lines) {
    $yaml_parser = new \Symfony\Component\Yaml\Yaml;
    $yaml = $yaml_parser->dump($lines, 2, 2);
    //drush_print_r($yaml);

    // Because the yaml is all built for us, this is just a singleton array.
    return [$yaml];
  }

}
