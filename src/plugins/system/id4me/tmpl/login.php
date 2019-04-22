<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.ID4Me
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
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
		if (Joomla.ID4Me)
		{
			Joomla.ID4Me('.login > form, form#login-form', {
				buttonimage: " . json_encode(HTMLHelper::_('image', 'plg_system_id4me/id4me-start-login.svg', '', null, true, 1)) . ",
				loginimage: " . json_encode(HTMLHelper::_('image', 'plg_system_id4me/id4me-login-button.svg', '', null, true, 1)) . ",
				token: '" . HTMLHelper::_('form.token') . "',
				formAction: '" . str_replace('/administrator/', '/', Route::_(
					str_replace('{client}', Factory::getApplication()->getName(), self::$formActionLoginUrl))) . "'
			});
		}
	  });
	})(document, Joomla);
";

Factory::getDocument()->addScriptDeclaration($js);
