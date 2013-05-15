<?php
/**
 * @package    FrameworkOnFramework
 * @copyright  Copyright (C) 2010 - 2012 Akeeba Ltd. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// Protect from unauthorized access
defined('_JEXEC') or die();

/**
 * Part of the FOF Platform Abstraction Layer. It implements everything that
 * depends on the platform FOF is running under, e.g. the Joomla! CMS front-end,
 * the Joomla! CMS back-end, a CLI Joomla! Platform app, a bespoke Joomla!
 * Platform / Framework web application and so on.
 *
 * This is the abstract class implementing some basic housekeeping functionality
 * and provides the static interface to get the appropriate Platform object for
 * use in the rest of the framework.
 *
 * @since 2.1
 */
abstract class FOFPlatform implements FOFPlatformInterface
{
	/**
	 * The ordering for this platform class. The lower this number is, the more
	 * important this class becomes. Most important enabled class ends up being
	 * used.
	 *
	 * @var  integer
	 */
	public $ordering = 100;

	/**
	 * Caches the enabled status of this platform class.
	 *
	 * @var  boolean
	 */
	protected $isEnabled = null;

	/**
	 * The list of paths where platform class files will be looked for
	 *
	 * @var  array
	 */
	static protected $paths = array();

	/**
	 * The platform class instance which will be returned by getInstance
	 *
	 * @var  FOFPlatformInterface
	 */
	static protected $instance = null;

	/**
	 * Register a path where platform files will be looked for. These take
	 * precedence over the built-in platform files.
	 *
	 * @param   string  $path  The path to add
	 *
	 * @return  void
	 */
	static public function registerPlatformPath($path)
	{
		if (!in_array($path, self::$paths))
		{
			self::$paths[] = $path;
			self::$instance = null;
		}
	}

	/**
	 * Unregister a path where platform files will be looked for.
	 *
	 * @param  string  $path  The path to remove
	 *
	 * @return  void
	 */
	static public function unregisterPlatformPath($path)
	{
		$pos = array_search($path, self::$paths);

		if ($pos !== false)
		{
			unset(self::$paths[$pos]);
			self::$instance = null;
		}
	}

	/**
	 * Force a specific platform object to be used
	 *
	 * @param   FOFPlatformInterface  $instance  The Platform object to be used
	 *
	 * @return  void
	 */
	static public function forceInstance(FOFPlatformInterface $instance)
	{
		self::$instance = $instance;
	}

	/**
	 * Find and return the most relevant platform object
	 *
	 * @return  FOFPlatformInterface
	 */
	static public function getInstance()
	{
		if (!is_object(self::$instance))
		{
			// Get the paths to look into
			$paths = array(__DIR__);
			if (is_array(self::$paths))
			{
				$paths = array_merge(array(__DIR__), self::$paths);
			}
			$paths = array_unique($paths);

			// Loop all paths
			JLoader::import('joomla.filesystem.folder');
			foreach ($paths as $path)
			{
				// Get the .php files containing platform classes
				$files = JFolder::files($path, '[a-z0-9]\.php$', false, true, array('interface.php', 'platform.php'));
				if (!empty($files))
				{
					foreach ($files as $file)
					{
						// Get the class name for this platform class
						$base_name = basename($file, '.php');
						$class_name = 'FOFPlatform' . ucfirst($base_name);

						// Load the file if the class doesn't exist
						if (!class_exists($class_name))
						{
							@include_once $file;
						}

						// If the class still doesn't exist this file didn't
						// actually contain a platform class; skip it
						if (!class_exists($class_name))
						{
							continue;
						}

						// If it doesn't implement FOFPlatformInterface, skip it
						if (!class_implements($class_name, 'FOFPlatformInterface'))
						{
							continue;
						}

						// Get an object of this platform
						$o = new $class_name;

						// If it's not enabled, skip it
						if (!$o->isEnabled)
						{
							continue;
						}

						if (is_object(self::$instance))
						{
							// Replace self::$instance if this object has a
							// lower order number
							$current_order = self::$instance->getOrdering();
							$new_order = $o->getOrdering();

							if ($new_order < $current_order)
							{
								self::$instance = null;
								self::$instance = $o;
							}
						}
						else
						{
							// There is no self::$instance already, so use the
							// object we just created.
							self::$instance = $o;
						}
					}
				}
			}
		}

		return self::$instance;
	}


	/**
	 * Returns the ordering of the platform class.
	 *
	 * @see FOFPlatformInterface::getOrdering()
	 *
	 * @return  integer
	 */
	public function getOrdering()
	{
		return $this->ordering;
	}

	/**
	 * Is this platform enabled?
	 *
	 * @see FOFPlatformInterface::isEnabled()
	 *
	 * @return  boolean
	 */
	public function isEnabled()
	{
		if (is_null($this->isEnabled))
		{
			$this->isEnabled = false;
		}

		return $isEnabled;
	}

	/**
	 * Returns the base (root) directories for a given component.
	 *
	 * @see FOFPlatformInterface::getComponentBaseDirs()
	 *
	 * @param   string  $component  The name of the component. For Joomla! this
	 *                              is something like "com_example"
	 *
	 * @return  array  A hash array with keys main, alt, site and admin.
	 */
	public function getComponentBaseDirs($component)
	{
		return array(
			'main'	=> '',
			'alt'	=> '',
			'site'	=> '',
			'admin'	=> '',
		);
	}

	/**
	 * Return a list of the view template directories for this component.
	 *
	 * @see FOFPlatformInterface::getViewTemplateDirs()
	 *
	 * @param   string  $component  The name of the component. For Joomla! this
	 *                              is something like "com_example"
	 *
	 * @return  array
	 */
	public function getViewTemplateDirs($component)
	{
		return array();
	}

	/**
	 * Load the translation files for a given component.
	 *
	 * @see FOFPlatformInterface::loadTranslations()
	 *
	 * @param   string  $component  The name of the component. For Joomla! this
	 *                              is something like "com_example"
	 *
	 * @return  void
	 */
	public function loadTranslations($component)
	{
		return null;
	}

	/**
	 * Authorise access to the component in the back-end.
	 *
	 * @see FOFPlatformInterface::authorizeAdmin()
	 *
	 * @param   string  $component  The name of the component.
	 *
	 * @return  boolean  True to allow loading the component, false to halt loading
	 */
	public function authorizeAdmin($component)
	{
		return true;
	}

	/**
	 * This method will try retrieving a variable from the request (input) data.
	 *
	 * @see FOFPlatformInterface::getUserStateFromRequest()
	 *
	 * @param   string    $key           The user state key for the variable
	 * @param   string    $request       The request variable name for the variable
	 * @param   FOFInput  $input         The FOFInput object with the request (input) data
	 * @param   mixed     $default       The default value. Default: null
	 * @param   string    $type          The filter type for the variable data. Default: none (no filtering)
	 * @param   boolean   $setUserState  Should I set the user state with the fetched value?
	 *
	 * @return  mixed  The value of the variable
	 */
	public function getUserStateFromRequest($key, $request, $input, $default = null, $type = 'none', $setUserState = true)
	{
		return $input->get($request, $default, $type);
	}

	/**
	 * Load plugins of a specific type. Obviously this seems to only be required
	 * in the Joomla! CMS.
	 *
	 * @see FOFPlatformInterface::importPlugin()
	 *
	 * @param   string  $type  The type of the plugins to be loaded
	 *
	 * @return void
	 */
	public function importPlugin($type)
	{

	}

	/**
	 * Execute plugins (system-level triggers) and fetch back an array with
	 * their return values.
	 *
	 * @see FOFPlatformInterface::runPlugins()
	 *
	 * @param   string  $event  The event (trigger) name, e.g. onBeforeScratchMyEar
	 * @param   array   $data   A hash array of data sent to the plugins as part of the trigger
	 *
	 * @return  array  A simple array containing the resutls of the plugins triggered
	 */
	public function runPlugins($event, $data)
	{
		return array();
	}

	/**
	 * Perform an ACL check.
	 *
	 * @see FOFPlatformInterface::authorise()
	 *
	 * @param   string  $action     The ACL privilege to check, e.g. core.edit
	 * @param   string  $assetname  The asset name to check, typically the component's name
	 *
	 * @return  boolean  True if the user is allowed this action
	 */
	public function authorise($action, $assetname)
	{
		return true;
	}

	/**
	 * Is this the administrative section of the component?
	 *
	 * @see FOFPlatformInterface::isBackend()
	 *
	 * @return  boolean
	 */
	public function isBackend()
	{
		return true;
	}

	/**
	 * Is this the public section of the component?
	 *
	 * @see FOFPlatformInterface::isFrontend()
	 *
	 * @return  boolean
	 */
	public function isFrontend()
	{
		return true;
	}

	/**
	 * Is this a component running in a CLI application?
	 *
	 * @see FOFPlatformInterface::isCli()
	 *
	 * @return  boolean
	 */
	public function isCli()
	{
		return true;
	}

	/**
	 * Is AJAX re-ordering supported? This is 100% Joomla!-CMS specific. All
	 * other platforms should return false and never ask why.
	 *
	 * @see FOFPlatformInterface::supportsAjaxOrdering()
	 *
	 * @return  boolean
	 */
	public function supportsAjaxOrdering()
	{
		return true;
	}
}