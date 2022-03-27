<?php

/*
 * Pattern Engine Twig Loader Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * The Twig Pattern Loader has been modified from the FilesystemLoader
 * by Fabien Potencier <fabien@symfony.com>
 *
 */

namespace PatternLab\PatternEngine\Twig\Loaders\Twig;

use PatternLab\PatternEngine\Util;
use Twig\Loader\LoaderInterface as Twig_LoaderInterface;
use Twig\Loader\FilesystemLoader as Twig_FileSystemLoader;

/**
 * Loads template from the filesystem.
 *
 * @author Fabien Potencier <fabien@symfony.com>, Dave Olsen, http://dmolsen.com
 */
class PatternPartialLoader extends Twig_FileSystemLoader implements Twig_LoaderInterface {

  protected $patternPaths = [];
  protected $extension    = '.twig';
  protected $patternUtil;

  /**
   * Constructor.
   *
   * @param string|array $paths    A path or an array of paths where to look for templates
   * @param string|null  $rootPath The root path common to all relative paths (null for getcwd())
   * @param array|null patternPaths array of patterns in theme.
   */
  public function __construct($paths = [], string $rootPath = null, $patternPaths = []) {
    parent::__construct($paths, $rootPath);
    $options['patternPaths'] = $patternPaths['patternPaths'];

    // Load some extra functions to help with manipulating pattern info.
    $this->patternUtil = new Util($options);

  }

  /**
   * @return string|null
   */
  protected function findTemplate(string $name, bool $throw = true) {

    list($partialName, $styleModifier, $parameters) = $this->patternUtil->getPartialInfo($name);

    $name = $this->patternUtil->getFileName($partialName, $this->extension);
    return parent::findTemplate($name, $throw);
  }

}
