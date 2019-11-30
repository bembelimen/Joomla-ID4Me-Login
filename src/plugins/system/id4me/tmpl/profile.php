<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.ID4Me
 *
 * @copyright   Copyright (C) 2019 Benjamin Trenkle. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;

HTMLHelper::_('stylesheet', 'plg_system_id4me/id4me.css', ['relative' => true]);
HTMLHelper::_('script', 'plg_system_id4me/id4me.min.js', ['relative' => true]);

$js = "
	(function(document, Joomla)
	{
	  document.addEventListener('DOMContentLoaded', function()
	  {
		if (Joomla.ID4Me && Joomla.ID4Me.profile)
		{
			Joomla.ID4Me.profile('jform_id4me_identifier', 'jform_id4me_issuersub', {
				formAction: '" . str_replace('/administrator/', '/', Route::_(
					str_replace('{client}', Factory::getApplication()->getName(), self::$redirectValidateUrl))) . "'
			});
		}
	  });
	})(document, Joomla);
";

Factory::getDocument()->addScriptDeclaration($js);
