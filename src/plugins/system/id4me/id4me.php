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
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\CMS\User\UserHelper;

class PlgSystemId4me extends CMSPlugin
{
	static $validateUrl = 'option=com_ajax&plugin=ID4MeLogin&format=raw';
	static $loginUrl = 'index.php?option=com_ajax&plugin=ID4MePrepare&format=raw';

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

	protected function getAuthorizationUrl($issuerConfiguration, $identifier)
	{

		$type = 'native';

		$registrationEndpoint = (string) $issuerConfiguration->get('registration_endpoint');
		$registrationResult = $this->registerService($registrationEndpoint, $type);

		$claimsSupported = $issuerConfiguration->get('claims_supported');
		$claims = $this->getUserInfoClaims($claimsSupported);

		$authorizationEndpoint = $issuerConfiguration->get('authorization_endpoint');
		
		$authorizationUrl = Uri::getInstance($authorizationEndpoint);
		$authorizationUrl->setVar('claims', urlencode($claims));
		$authorizationUrl->setVar('scope', 'openid');
		$authorizationUrl->setVar('response_type', 'code');
		$authorizationUrl->setVar('client_id', $registrationResult->get('client_id'));
		$authorizationUrl->setVar('redirect_uri', urlencode($this->getValidateUrl($type)));
		$authorizationUrl->setVar('login_hint', $identifier);
		$authorizationUrl->setVar('state', UserHelper::genRandomPassword(100));
		$authorizationUrl->setScheme($type === 'web' ? 'https' : 'http');

		return $authorizationUrl->toString();
	}


	protected function getUserInfoClaims($claimsSupported)
	{
		return json_encode([
			'userinfo' => [
				"given_name" => ["essential" => true, "reason" => "In order to create an account"],
				"email" => ["essential" => true, "reason" => "To assure smooth communication"],
				"email_verified" => ["reason" => "To skip the E-mail verification"],
			],
			'id_token' => [
				"auth_time" => ["essential" => true],
			]
		]);
	}


	protected function getValidateUrl($type)
	{
		$redirectUrl = Uri::getInstance();
		$redirectUrl->setQuery(self::$validateUrl);
		$redirectUrl->setScheme($type === 'web' ? 'https' : 'http');

		return $redirectUrl->toString();
	}

	protected function registerService($registrationEndpoint, $type = 'web')
	{
		$registrationDataJSON = json_encode(array(
			'client_name' => 'Acme Service',
			'application_type' => $type,
			'redirect_uris' => [$this->getValidateUrl($type)],
		));

		$registrationResult = HttpFactory::getHttp()->post($registrationEndpoint, $registrationDataJSON, ['Content-Type' => 'application/json']);

		if (empty($registrationResult->body) || $registrationResult->code != '200')
		{
			return false;
		}

		return new Registry($registrationResult->body);
	}

	public function onAjaxID4MePrepare()
	{
		$identifier = $this->app->input->getString('id4me-identifier');

		// Validate identifier: 
		$issuer = $this->getIssuerbyIdentifier($identifier);

		// We can't do anythng when there is no issuer
		if (!$issuer)
		{
			return false;
		}

		$issuerUrl = Uri::getInstance($issuer);
		$issuerUrl->setScheme('https');

		$issuerConfiguration = $this->getOpenIdConfiguration($issuerUrl->toString());

		$authorizationUrl = $this->getAuthorizationUrl($issuerConfiguration, $identifier);

		if (!$authorizationUrl)
		{
			return false;
		}

		$this->app->redirect($authorizationUrl);
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

	protected function getOpenIdConfiguration($issuer)
	{
		// https://id.test.denic.de/.well-known/openid-configuration
		$openIdConfiguration = HttpFactory::getHttp()->get(
			$issuer . '/.well-known/openid-configuration'
		);

		if (empty($openIdConfiguration->body) || $openIdConfiguration->code != '200')
		{
			return false;
		}

		return new Registry($openIdConfiguration->body);
	}

}