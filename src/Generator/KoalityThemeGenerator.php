<?php

namespace Drupal\koality_theme\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;
use Drupal\Component\Serialization\Yaml;
use Drupalfinder\DrupalFinder;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class KoalityThemeGenerator.
 */
class KoalityThemeGenerator extends Generator {


  /**
   * @var Manager
   */
  protected $extensionManager;

  /**
   * AuthenticationProviderGenerator constructor.
   *
   * @param Manager $extensionManager
   */
  public function __construct(
    Manager $extensionManager
  ) {
    $this->extensionManager = $extensionManager;
  }

  /**
   * {@inheritdoc}
   */
  public function generate(array $parameters) {
    $dir = $parameters['dir'];
    $breakpoints = $parameters['breakpoints'];
    $machine_name = $parameters['machine_name'];
    $parameters['type'] = 'theme';

    $dir = ($dir == '/' ? '' : $dir) . '/' . $machine_name;
    if (file_exists($dir)) {
      if (!is_dir($dir)) {
        throw new \RuntimeException(
          sprintf(
            'Unable to generate the bundle as the target directory "%s" exists but is a file.',
            realpath($dir)
          )
        );
      }
      $files = scandir($dir);
      if ($files != ['.', '..']) {
        throw new \RuntimeException(
          sprintf(
            'Unable to generate the bundle as the target directory "%s" is not empty.',
            realpath($dir)
          )
        );
      }
      if (!is_writable($dir)) {
        throw new \RuntimeException(
          sprintf(
            'Unable to generate the bundle as the target directory "%s" is not writable.',
            realpath($dir)
          )
        );
      }
    }

    if ($parameters['base_theme_regions'] && $parameters['base_theme']) {
      $defaultRegions = Yaml::decode(file_get_contents($parameters['base_theme_path']));
      $parameters['base_theme_regions'] = $defaultRegions['regions'];
      $parameters['base_theme_regions_hidden'] = $defaultRegions['regions_hidden'];
    }

    $themePath = $dir . '/';
    // $drupalFinder = new DrupalFinder();
    // $drupalFinder->locateRoot();
    $module_template_dir = drupal_get_path('module', 'koality_theme') . '/templates/';
    $this->addSkeletonDir('/var/www/docroot/' . $module_template_dir);
    $test = '';

    $this->renderFile(
      'theme/koality-info.yml.twig',
      $themePath . $machine_name . '.info.yml',
      $parameters
    );

    $this->renderFile(
      'theme/koality-theme.twig',
      $themePath . $machine_name . '.theme',
      $parameters
    );

    $this->renderFile(
      'theme/koality-libraries.yml.twig',
      $themePath . $machine_name . '.libraries.yml',
      $parameters
    );

    $this->renderFile(
      'theme/koality-package.json.twig',
      $themePath . 'package.json',
      $parameters
    );

    if ($breakpoints) {
      $this->renderFile(
        'theme/koality-breakpoints.yml.twig',
        $themePath . $machine_name . '.breakpoints.yml',
        $parameters
      );
    }

    $fileSystem = new Filesystem();
    $fileSystem->mirror($module_template_dir . 'theme/src', $themePath . '/src');
    $fileSystem->mirror($module_template_dir . 'theme/base-level-files', $themePath);
  }
}
