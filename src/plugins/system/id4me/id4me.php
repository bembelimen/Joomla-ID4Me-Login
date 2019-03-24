<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.ID4Me
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

require_once __DIR__ . '/library/vendor/autoload.php';

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;

class PlgSystemId4me extends CMSPlugin
{
	static $redirect_url = 'index.php?option=com_ajax&plugin=ID4MeLoginVLogin&format=raw';
	static $login_url = 'index.php?option=com_ajax&plugin=ID4MePrepare&format=raw';

	protected $httpClient;
	protected $id4Me;

	protected $app;

	protected $autoloadLanguage = true;

	public function onBeforeRender()
	{
		if ($this->app->isClient('site') || ($this->app->isClient('administrator') && Factory::getUser()->guest))
		{
			// Load JS
			echo $this->loadLayout('login');

			Text::script('PLG_SYSTEM_ID4ME_IDENTIFIER_LABEL');
		}
	}

	public static function onAjaxID4MePrepare()
	{
		$identifier = $this->app->input->get('id4me-identifier');

		echo $identifier;

		// Validate identifier
	}

	protected function loadLayout($layout)
	{
		$path = PluginHelper::getLayoutPath('system', 'id4me', $layout);

		ob_start();
		include $path;

		$result = ob_get_contents();

		ob_clean();

		return $result;
	}

/*
		public function run()
	{
		$identifier = 'idtemp2.id4me.family';
		echo PHP_EOL;
		echo '***********************************Discovery***************************************';
		echo PHP_EOL;
		$authorityName = $this->id4Me->discover($identifier);
		var_dump($authorityName);
		echo PHP_EOL;
		echo PHP_EOL;
		echo '***********************************Registration***************************************';
		echo PHP_EOL;
		$openIdConfig = $this->id4Me->getOpenIdConfig($authorityName);
		var_dump($openIdConfig);
		echo PHP_EOL;
		$client = $this->id4Me->register(
			$openIdConfig,
			$identifier,
			sprintf('http://www.rezepte-elster.de/id4me.php', $identifier)
		);
		var_dump($client);
		echo PHP_EOL;
		echo PHP_EOL;
		echo '***********************************Authenticate***************************************';
		echo PHP_EOL;
		echo "Do following steps:\n";
		echo "1.Please click on login link below\n";
		echo "2.Login with password '123456'\n";
		echo "3.Copy and Paste 'code' value from corresponding url query parameter into code input prompt field below'\n";
		$authorizationUrl = $this->id4Me->getAuthorizationUrl(
			$openIdConfig, $client->getClientId(), $identifier, $client->getActiveRedirectUri(), 'idtemp2.id4me.family'
		);
		var_dump($authorizationUrl);exit;
		echo PHP_EOL;
		echo PHP_EOL;
		$accessTokens = $this->id4Me->getAccessTokens(
			$openIdConfig,
			readline('code:'),
			sprintf('http://www.rezepte-elster.de/id4me.php', $identifier),
			$client->getClientId(),
			$client->getClientSecret()
		);

		var_dump($accessTokens);
		echo PHP_EOL;
		echo PHP_EOL;
	}
*/

	/**
	 * Try to read the issuer information from the DNS TXT Record.
	 *
	 * @param  string  $hostname  The identifier/Domain of the user to authenticate
	 *
	 * @since  1.0.0
	 */
	private function getIssuerbyHostname($hostname)
	{
		$hostname = '_openid.' . $hostname;
		$records = dns_get_record($hostname, DNS_TXT);

		if (empty($records) || !is_array($records))
		{
			return false;
		}

		$issuer = false;

		$rexep = '/iss=([^;]+)/';

		foreach ($records as $record)
		{
			if (!isset($record['txt']))
			{
				continue;
			}

			if (preg_match($rexep, $record['txt'], $match))
			{
				return $match[1];
			}
		}

		return false;
	}

		/**
	 * Returns the issuer information from the DNS TXT Record.
	 * As per definition we try the complete path and check for an valid TXT record.
	 *
	 * @param  string  $identifier  The identifier/Domain of the user to authenticate
	 *
	 * @since  1.0.0
	 */
	protected function getIssuerbyIdentifier($identifier)
	{
		$hostparts = explode('.', $identifier);
		$totalCountOfHostparts = count($hostparts);

		$i = 0;
		$result = false;

		do
		{
			$reducedIdentifier = implode('.', $hostparts);
			$result = $this->getIssuerbyHostname($reducedIdentifier);

			if (!is_bool($result))
			{
				return $result;
			}

			array_shift($hostparts);
			++$i;
		}
		while ($i <= $totalCountOfHostparts);

		return false;
	}

	protected static function getOpenId($issuer, $url)
	{
		$server = new OAuth2\Server;

		$config = $server->handleTokenRequest($issuer);

		print_r($config);exit;

		return $server;
	}


}