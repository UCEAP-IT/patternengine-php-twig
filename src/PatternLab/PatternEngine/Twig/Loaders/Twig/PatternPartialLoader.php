<?php

/*!
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

use \PatternLab\PatternEngine\Util;
use Twig\Error\LoaderError as Twig_Error_Loader;

class PatternPartialLoader implements \Twig_LoaderInterface, \Twig_ExistsLoaderInterface {

	/** Identifier of the main namespace. */
	const MAIN_NAMESPACE = '__main__';

	protected $paths        = array();
	protected $cache        = array();
	protected $patternPaths = array();
	protected $extension    = '.twig';

	/**
	 * Constructor.
	 *
	 * @param string|array $paths A path or an array of paths where to look for templates
	 */
	public function __construct($paths = array(),$patternPaths = array()) {
		if ($paths) {
			$this->setPaths($paths);
		}
		$options['patternPaths'] = $patternPaths['patternPaths'];

		// load some extra functions to help with manipulating pattern info
		$this->patternUtil = new Util($options);

	}

	/**
	 * Returns the paths to the templates.
	 *
	 * @param string $namespace A path namespace
	 *
	 * @return array The array of paths where to look for templates
	 */
	public function getPaths($namespace = self::MAIN_NAMESPACE) {
		return isset($this->paths[$namespace]) ? $this->paths[$namespace] : array();
	}

	/**
	 * Returns the path namespaces.
	 *
	 * The main namespace is always defined.
	 *
	 * @return array The array of defined namespaces
	 */
	public function getNamespaces() {
		return array_keys($this->paths);
	}

	/**
	 * Sets the paths where templates are stored.
	 *
	 * @param string|array $paths	 A path or an array of paths where to look for templates
	 * @param string	   $namespace A path namespace
	 */
	public function setPaths($paths, $namespace = self::MAIN_NAMESPACE) {
		if (!is_array($paths)) {
			$paths = array($paths);
		}

		$this->paths[$namespace] = array();
		foreach ($paths as $path) {
			$this->addPath($path, $namespace);
		}
	}

	/**
	 * Adds a path where templates are stored.
	 *
	 * @param string $path	  A path where to look for templates
	 * @param string $namespace A path name
	 *
	 * @throws Twig_Error_Loader
	 */
	public function addPath($path, $namespace = self::MAIN_NAMESPACE) {
		// invalidate the cache
		$this->cache = array();

		if (!is_dir($path)) {
			throw new Twig_Error_Loader(sprintf('The "%s" directory does not exist.', $path));
		}

		$this->paths[$namespace][] = rtrim($path, '/\\');
	}

	/**
	 * Prepends a path where templates are stored.
	 *
	 * @param string $path	  A path where to look for templates
	 * @param string $namespace A path name
	 *
	 * @throws Twig_Error_Loader
	 */
	public function prependPath($path, $namespace = self::MAIN_NAMESPACE) {
		// invalidate the cache
		$this->cache = array();

		if (!is_dir($path)) {
			throw new Twig_Error_Loader(sprintf('The "%s" directory does not exist.', $path));
		}

		$path = rtrim($path, '/\\');

		if (!isset($this->paths[$namespace])) {
			$this->paths[$namespace][] = $path;
		} else {
			array_unshift($this->paths[$namespace], $path);
		}

	}

	/**
	 * {@inheritdoc}
	 */
	public function getSource($name) {
		return file_get_contents($this->findTemplate($name));
	}

	/**
	 * {@inheritdoc}
	 */
	public function getCacheKey($name) {
		return $this->findTemplate($name);
	}

	/**
	 * {@inheritdoc}
	 */
	public function exists($name) {

		$name = $this->normalizeName($name);

		if (isset($this->cache[$name])) {
			return true;
		}

		try {
			$this->findTemplate($name);

			return true;
		} catch (Twig_Error_Loader $exception) {
			return false;
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function isFresh($name, $time) {
		return filemtime($this->findTemplate($name)) <= $time;
	}

	protected function findTemplate($name) {

		list($partialName,$styleModifier,$parameters) = $this->patternUtil->getPartialInfo($name);

		$name = $this->patternUtil->getFileName($partialName,$this->extension);

		$name = $this->normalizeName($name);

		if (isset($this->cache[$name])) {
			return $this->cache[$name];
		}

		$this->validateName($name);

		$namespace = self::MAIN_NAMESPACE;
		$shortname = $name;
		if (isset($name[0]) && '@' == $name[0]) {
			if (false === $pos = strpos($name, '/')) {
				throw new Twig_Error_Loader(sprintf('Malformed namespaced template name "%s" (expecting "@namespace/template_name").', $name));
			}

			$namespace = substr($name, 1, $pos - 1);
			$shortname = substr($name, $pos + 1);
		}

		if (!isset($this->paths[$namespace])) {
			throw new Twig_Error_Loader(sprintf('There are no registered paths for namespace "%s".', $namespace));
		}

		foreach ($this->paths[$namespace] as $path) {
			if (is_file($path.'/'.$shortname)) {
				return $this->cache[$name] = $path.'/'.$shortname;
			}
		}


		throw new Twig_Error_Loader(sprintf('Unable to find template "%s" (looked into: %s).', $partialName, implode(', ', $this->paths[$namespace])));
	}

	protected function normalizeName($name) {
		return preg_replace('#/{2,}#', '/', strtr((string) $name, '\\', '/'));
	}

	protected function validateName($name) {

		if (false !== strpos($name, "\0")) {
			throw new Twig_Error_Loader('A template name cannot contain NUL bytes.');
		}

		$name = ltrim($name, '/');
		$parts = explode('/', $name);
		$level = 0;
		foreach ($parts as $part) {
			if ('..' === $part) {
				--$level;
			} elseif ('.' !== $part) {
				++$level;
			}

			if ($level < 0) {
				throw new Twig_Error_Loader(sprintf('Looks like you try to load a template outside configured directories (%s).', $name));
			}
		}

	}

}
