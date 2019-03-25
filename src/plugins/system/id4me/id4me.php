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

	protected $type = 'native';

	public function onBeforeRender()
	{
		if ($this->app->isClient('site') /*|| ($this->app->isClient('administrator') && Factory::getUser()->guest)*/)
		{
			// Load JS
			echo $this->loadLayout('login');

			Text::script('PLG_SYSTEM_ID4ME_IDENTIFIER_LABEL');
		}
	}

	/**
	 * Uses login credentials and redirect to the login field from the provider
	 *
	 * @return boolean
	 */
	public function onAjaxID4MePrepare()
	{
		$identifier = $this->app->input->getString('id4me-identifier');

		$this->app->setUserState('id4me.identifier', $identifier);

		// Validate identifier:
		$issuer = $this->getIssuerbyIdentifier($identifier);

		$issuerUrl = Uri::getInstance($issuer);
		$issuerUrl->setScheme('https');

		$issuerConfiguration = $this->getOpenIdConfiguration($issuerUrl->toString());

		$registrationEndpoint = (string) $issuerConfiguration->get('registration_endpoint');
		$registrationResult = $this->registerService($registrationEndpoint);

		$client_id = $registrationResult->get('client_id');
		$client_secret = $registrationResult->get('client_secret');
		$state = UserHelper::genRandomPassword(100);

		$this->app->setUserState('id4me.client_id', $client_id);
		$this->app->setUserState('id4me.client_secret', $client_secret);
		$this->app->setUserState('id4me.state', $state);

		$authorizationUrl = $this->getAuthorizationUrl($issuerConfiguration, $identifier, $client_id, $state);

		if (!$authorizationUrl)
		{
			return false;
		}

		$this->app->redirect($authorizationUrl);
	}

	/**
	 * Endpoint for the final login/registration process
	 */
	public function onAjaxID4MeLogin()
	{
		$code = $this->app->input->get('code');
		$state = $this->app->input->get('state');

		$identifier = $this->app->getUserState('id4me.identifier');

		// Validate identifier:
		$issuer = $this->getIssuerbyIdentifier($identifier);

		$issuerUrl = Uri::getInstance($issuer);
		$issuerUrl->setScheme('https');

		$issuerConfiguration = $this->getOpenIdConfiguration($issuerUrl->toString());

		$registrationEndpoint = (string) $issuerConfiguration->get('registration_endpoint');
		$registrationResult = $this->registerService($registrationEndpoint);

		$client_id = $this->app->getUserState('id4me.client_id');
		$client_secret = $this->app->getUserState('id4me.client_secret');

		$tokenEndpoint = (string) $issuerConfiguration->get('token_endpoint');

		$tokens = $this->getAuthTokens($code, $tokenEndpoint, $client_id, $client_secret);

		$bearerToken = $this->validateAuthTokens($tokens);

		$userinfoEndpoint = (string) $issuerConfiguration->get('userinfo_endpoint');

		$claims = $this->getClaims($userinfoEndpoint, $bearerToken);

		echo '<pre>';print_r($claims);exit('foo');

	}

	protected function dieHard()
	{
		throw new Exception(Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'));
	}

	protected function getClaims($userinfoEndpoint, $token)
	{
		$headers = [
			'Accept' => 'application/jwt',
			'Authorization' => 'Bearer ' . $token
		];

		$claimResult = HttpFactory::getHttp()->get($userinfoEndpoint, $headers);

		if (empty($claimResult->body) || $claimResult->code != '200')
		{
			$this->dieHard();
		}

		$claims = new Registry($claimResult->body);

		$this->validateClaims($claims);

		return $this->loadRealClaims($claims);
	}

	protected function loadRealClaims($claimResult)
	{
		$claimNames = $claimResult->get('_claim_names');
		$claimRessources = (array) $claimResult->get('_claim_sources');

		$endpoints = [];

		$user = new stdClass;

		if (!isset($claimRessources[$claimNames->given_name])|| !isset($claimRessources[$claimNames->email]))
		{
			$this->dieHard();
		}

		$user->name = $this->loadClaimByKey($claimRessources[$claimNames->given_name]->endpoint, $claimRessources[$claimNames->given_name]->access_token, 'given_name');
		$user->email = $this->loadClaimByKey($claimRessources[$claimNames->given_name]->endpoint, $claimRessources[$claimNames->given_name]->access_token, 'endpoint');

		print_r($user);exit;

		return $user;
	}

	protected function loadClaimByKey($endpoint, $token, $name)
	{
		static $ressources = [];

		if (isset($ressources[$endpoint][$token]))
		{
			return $ressources[$endpoint][$token]->get($name);
		}

		$headers = [
			'Accept' => 'application/jwt',
			'Authorization' => 'Bearer ' . $token
		];

		$claimResult = HttpFactory::getHttp()->get($endpoint, $headers);

		if (empty($claimResult->body) || $claimResult->code != '200')
		{
			$this->dieHard();
		}

		$ressources[$endpoint][$token] = new Registry($claimResult->body);

		return $ressources[$endpoint][$token]->get($name);
	}

	protected function validateCLaims($claimResult)
	{
		return true;
	}

	protected function getAuthTokens($code, $tokenEndpoint, $client_id, $client_secret)
	{
		$authTokenRequest = http_build_query([
			'grant_type' => 'authorization_code',
			'code' => $code,
			'redirect_uri' => $this->getValidateUrl(),
		]);

		$headers = [
			'Content-Type' => 'application/x-www-form-urlencoded',
			'Authorization' => 'Basic ' . base64_encode($client_id . ':' . $client_secret)
		];

		$registrationResult = HttpFactory::getHttp()->post($tokenEndpoint, $authTokenRequest, $headers);

		if (empty($registrationResult->body) || $registrationResult->code != '200')
		{
			$this->dieHard();
		}

		return new Registry($registrationResult->body);
	}

	protected function validateAuthTokens($tokens)
	{
		// @TODO token validation
		if ($tokens->get('access_token'))
		{
			return $tokens->get('access_token');
		}

		$this->dieHard();
	}

	/**
	 * Loads an overrideable tmpl file
	 *
	 * @param string $layout The layout name
	 *
	 * @return string  The final template content
	 */
	protected function loadLayout($layout)
	{
		$path = PluginHelper::getLayoutPath('system', 'id4me', $layout);

		ob_start();
		include $path;

		$result = ob_get_contents();

		ob_clean();

		return $result;
	}

	protected function getAuthorizationUrl($issuerConfiguration, $identifier, $client_id, $state)
	{
		$claimsSupported = $issuerConfiguration->get('claims_supported');
		$claims = $this->getUserInfoClaims($claimsSupported);

		$authorizationEndpoint = $issuerConfiguration->get('authorization_endpoint');

		$authorizationUrl = Uri::getInstance($authorizationEndpoint);
		$authorizationUrl->setVar('claims', urlencode($claims));
		$authorizationUrl->setVar('scope', 'openid');
		$authorizationUrl->setVar('response_type', 'code');
		$authorizationUrl->setVar('client_id', $client_id);
		$authorizationUrl->setVar('redirect_uri', urlencode($this->getValidateUrl()));
		$authorizationUrl->setVar('login_hint', $identifier);
		$authorizationUrl->setVar('state', $state);
		$authorizationUrl->setScheme($this->type === 'web' ? 'https' : 'http');

		return $authorizationUrl->toString();
	}

	/**
	 *
	 *
	 * @param boolean $claimsSupported
	 *
	 * @return void
	 */
	protected function getUserInfoClaims($claimsSupported)
	{
		return json_encode([
			'userinfo' => [
				'given_name' => ['essential' => true, 'reason' => 'In order to create an account'],
				'email' => ['essential' => true, 'reason' => 'To assure smooth communication'],
				'email_verified' => ['reason' => 'To skip the E-mail verification'],
			],
			'id_token' => [
				'auth_time' => ['essential' => true],
			]
		]);
	}

	/**
	 *
	 *
	 *
	 * @return string  The validateion URL
	 */
	protected function getValidateUrl()
	{
		$redirectUrl = Uri::getInstance();
		$redirectUrl->setQuery(self::$validateUrl);
		$redirectUrl->setScheme($this->type === 'web' ? 'https' : 'http');

		return $redirectUrl->toString();
	}

	/**
	 *
	 * @param type $registrationEndpoint
	 *
	 * @return boolean|Registry
	 */
	protected function registerService($registrationEndpoint)
	{
		$registrationDataJSON = json_encode(array(
			'client_name' => $this->app->get('sitename'),
			'application_type' => $this->type,
			'redirect_uris' => [$this->getValidateUrl()],
		));

		$registrationResult = HttpFactory::getHttp()->post($registrationEndpoint, $registrationDataJSON, ['Content-Type' => 'application/json']);

		if (empty($registrationResult->body) || !in_array($registrationResult->code, ['200', '201']))
		{
			$this->dieHard();
		}

		return new Registry($registrationResult->body);
	}

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

		$this->dieHard();
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

		$this->dieHard();
	}

	/**
	 *
	 * @param type $issuer
	 *
	 * @return boolean|Registry
	 */
	protected function getOpenIdConfiguration($issuer)
	{
		// https://id.test.denic.de/.well-known/openid-configuration
		$openIdConfiguration = HttpFactory::getHttp()->get(
			$issuer . '/.well-known/openid-configuration'
		);

		if (empty($openIdConfiguration->body) || $openIdConfiguration->code != '200')
		{
			$this->dieHard();
		}

		return new Registry($openIdConfiguration->body);
	}

}