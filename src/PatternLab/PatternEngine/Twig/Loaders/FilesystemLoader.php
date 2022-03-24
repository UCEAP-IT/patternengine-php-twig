<?php

/*!
 * Twig Pattern Engine Loader Class - Filesystem
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Sets an instance of Twig to deal with rendering of templates that aren't patterns
 *
 */

namespace PatternLab\PatternEngine\Twig\Loaders;

use Twig\Loader\FilesystemLoader as Twig_Loader_Filesystem;
use Twig\Environment as Twig_Environment;
use PatternLab\Config;
use PatternLab\Dispatcher;
use PatternLab\PatternEngine\Loader;
use PatternLab\PatternEngine\Twig\TwigUtil;

class FilesystemLoader extends Loader {

  /**
   * Load a new Twig instance that uses the File System Loader.
   */
  public function __construct($options = []) {

    // set-up default vars.
    $twigDebug = Config::getOption("twigDebug");

    // set-up the paths to be searched for templates.
    $filesystemLoaderPaths   = [];
    $filesystemLoaderPaths[] = $options["templatePath"];
    $filesystemLoaderPaths[] = $options["partialsPath"];

    // See if source/_macros exists. if so add it to be searchable.
    $macrosPath = Config::getOption("sourceDir") . DIRECTORY_SEPARATOR . "_macros";
    if (is_dir($macrosPath)) {
      $filesystemLoaderPaths[] = $macrosPath;
    }

    // See if source/_layouts exists. if so add it to be searchable.
    $layoutsPath = Config::getOption("sourceDir") . DIRECTORY_SEPARATOR . "_layouts";
    if (is_dir($layoutsPath)) {
      $filesystemLoaderPaths[] = $layoutsPath;
    }

    // Set-up Twig.
    $twigLoader = new Twig_Loader_Filesystem($filesystemLoaderPaths);
    $instance   = new Twig_Environment($twigLoader, ["debug" => $twigDebug]);

    // Customize Twig.
    TwigUtil::setInstance($instance);
    // Disabling custom Twig Extensions for Filesystem loader as it is only used internally by PL for view all pages
    // TwigUtil::loadCustomExtensions();
    // @todo Determine if any custom things should be loaded for this
    TwigUtil::loadFilters();
    TwigUtil::loadFunctions();
    TwigUtil::loadTags();
    TwigUtil::loadTests();
    TwigUtil::loadDateFormats();
    TwigUtil::loadDebug();
    TwigUtil::loadMacros();

    // Set-up the dispatcher.
    $dispatcherInstance = Dispatcher::getInstance();
    $dispatcherInstance->dispatch("twigLoader.customize");
    $dispatcherInstance->dispatch("twigFilesystemLoader.customize");

    // Get the instance.
    $this->instance = TwigUtil::getInstance();

  }

  /**
   * Render a template.
   *
   * @param {Array}
   *   the options to be rendered by Twig
   *
   * @return {String}
   *   the rendered result
   */
  public function render($options = []) {

    return $this->instance->render($options["template"] . "." . Config::getOption("patternExtension"), $options["data"]);

  }

}
