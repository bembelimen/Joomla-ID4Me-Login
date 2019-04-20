<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.ID4Me
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

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

/**
 * Plugin class for ID4me
 *
 * @since  1.0
 */
class PlgSystemId4me extends CMSPlugin
{
	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var    boolean
	 * @since  1.0.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * Application object.
	 *
	 * @var    CMSApplication
	 * @since  1.0.0
	 */
	protected $app;

	/**
	 * The type of application we are runnig. We only use `native` when we are on localhost; Default is `web`
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected $applicationType = 'web';

	/**
	 * The redirect URL for the validation`
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected $redirectValidateUrl = 'option=com_ajax&plugin=ID4MeLogin&format=raw';

	/**
	 * The url used to trigger the login request to the identity provider
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected $formActionLoginUrl = 'index.php?option=com_ajax&plugin=ID4MePrepare&format=raw';

	/**
	 * Using this event we add our JaveScript and CSS code to add the id4me button to the login form.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function onBeforeRender()
	{
		if ($this->app->isClient('site') || ($this->app->isClient('administrator') && Factory::getUser()->guest))
		{
			// Load the language string
			Text::script('PLG_SYSTEM_ID4ME_IDENTIFIER_LABEL');

			// Load the layout with the JaveScript and CSS
			echo $this->loadLayout('login');

			// When we are at locahost we need to set the application type to native
			if (strpos(Uri::getInstance()->toString(), 'http://localhost/'))
			{
				$this->applicationType = 'native';
			}
		}
	}

	/**
	 * This com_ajax Endpoint detects, based on the identifier, the Issuer and redirects to the login page of the Issuer
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function onAjaxID4MePrepare()
	{
		$identifier = $this->app->input->getString('id4me-identifier');

		$this->app->setUserState('id4me.identifier', $identifier);

		// Validate identifier:
		$issuer = $this->getIssuerbyIdentifier($identifier);

		$issuerConfiguration = $this->getOpenIdConfiguration(Uri::getInstance($issuer)->setScheme('https')->toString());

		$registrationEndpoint = (string) $issuerConfiguration->get('registration_endpoint');
		$registrationResult = $this->registerService($registrationEndpoint);

		$clientId = $registrationResult->get('client_id');
		$clientSecret = $registrationResult->get('client_secret');
		$state = UserHelper::genRandomPassword(100);

		$this->app->setUserState('id4me.client_id', $clientId);
		$this->app->setUserState('id4me.client_secret', $clientSecret);
		$this->app->setUserState('id4me.state', $state);

		$authorizationUrl = $this->getAuthorizationUrl($issuerConfiguration, $identifier, $clientId, $state);

		if (!$authorizationUrl)
		{
			// We don't have an authorization URL so we can't do anything here.
			$this->app->enqueueMessage(Text::_('PLG_SYSTEM_ID4ME_NO_AUTHORIZATION_URL'), 'error');
			$this->app->redirect('index.php');
		}

		$this->app->redirect($authorizationUrl);
	}

	/**
	 * Endpoint for the final login/registration process
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function onAjaxID4MeLogin()
	{
		$issuerConfiguration = $this->getOpenIdConfiguration(
			Uri::getInstance(
				$this->getIssuerbyIdentifier(
					$this->app->getUserState('id4me.identifier')
				)
			)
			->setScheme('https')
			->toString()
		);

		$bearerToken = $this->validateAuthTokens(
			$this->getAuthTokens(
				$this->app->input->get('code'),
				$issuerConfiguration->get('token_endpoint'),
				$this->app->getUserState('id4me.client_id'),
				$this->app->getUserState('id4me.client_secret')
			)
		);

		$claims = $this->getClaims($issuerConfiguration->get('userinfo_endpoint'), $bearerToken);

	}

	/**
	 * Something unexpected happend throw an exception
	 *
	 * @return  void
	 * @throws  RuntimeException
	 *
	 * @since   1.0.0
	 */
	protected function dieHard()
	{
		throw new RuntimeException(Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'));
	}

	/**
	 * Get the claimed fields
	 *
	 * @param   string  $userinfoEndpoint  The User Info Endpoint URL
	 * @param   string  $token             The token to request the User Information
	 *
	 * @return  object
	 * @throws  RuntimeException
	 *
	 * @since   1.0.0
	 */
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

	/**
	 * Load the content of the claims so we can build our user object
	 *
	 * @param   Registry  $claims  An Registry Object containing the claims
	 *
	 * @return  object   An stdClass Object containing username and mail
	 *
	 * @since   1.0.0
	 */
	protected function loadRealClaims($claims)
	{
		$claimNames      = $claims->get('_claim_names');
		$claimRessources = (array) $claims->get('_claim_sources');

		$user = new stdClass;

		if (!isset($claimRessources[$claimNames->given_name])|| !isset($claimRessources[$claimNames->email]))
		{
			$this->dieHard();
		}

		$user->name = $this->loadClaimByKey(
			$claimRessources[$claimNames->given_name]->endpoint,
			$claimRessources[$claimNames->given_name]->access_token,
			'given_name'
		);
		$user->email = $this->loadClaimByKey(
			$claimRessources[$claimNames->email]->endpoint,
			$claimRessources[$claimNames->email]->access_token,
			'email'
		);

		return $user;
	}

	/**
	 * Reads the actual content of an claim by key
	 *
	 * @param   string  $endpoint  The claim endpoint
	 * @param   string  $token     The claim token
	 * @param   string  $name      The claim name
	 *
	 * @return  string  The string readed out of the claim
	 *
	 * @since   1.0.0
	 */
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

	/**
	 * Validate the claims and make sure all required fields is there.
	 *
	 * @param   Registry  $claims  An Registry Object containing the claims
	 *
	 * @return  boolean  True on success else false
	 *
	 * @since   1.0.0
	 */
	protected function validateClaims($claims)
	{
		return true;
	}

	/**
	 * Get the tokens for our clientId from the tokenEndpoint
	 *
	 * @param   string  $code           The code
	 * @param   string  $tokenEndpoint  The endpoint for requesting tokens
	 * @param   string  $clientId       Our clientID
	 * @param   string  $clientSecret   Our clientSecret
	 *
	 * @return  boolean  True on success else false
	 *
	 * @since   1.0.0
	 */
	protected function getAuthTokens($code, $tokenEndpoint, $clientId, $clientSecret)
	{
		$authTokenRequest = http_build_query(
			[
				'grant_type' => 'authorization_code',
				'code' => $code,
				'redirect_uri' => $this->getValidateUrl(),
			]
		);

		$headers = [
			'Content-Type' => 'application/x-www-form-urlencoded',
			'Authorization' => 'Basic ' . base64_encode($clientId . ':' . $clientSecret)
		];

		$tokenResult = HttpFactory::getHttp()->post($tokenEndpoint, $authTokenRequest, $headers);

		if (empty($tokenResult->body) || $tokenResult->code != '200')
		{
			$this->dieHard();
		}

		return new Registry($tokenResult->body);
	}

	/**
	 * Validate the auth token
	 *
	 * @param   Registry  $tokens  An Registry Object containing the tokens
	 *
	 * @return  string   The access Token
	 *
	 * @since   1.0.0
	 */
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
	 * @param   string  $layout  The layout name
	 *
	 * @return  string  The final template content
	 *
	 * @since   1.0.0
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

	/**
	 * Return the autorization URL
	 *
	 * @param   Registry  $issuerConfiguration  The Registry object with the issuer configuration
	 * @param   string    $identifier           The provided identifier
	 * @param   string    $clientId             Our clientId
	 * @param   string    $state                The random state
	 *
	 * @return  string  The Autorization URL
	 *
	 * @since   1.0.0
	 */
	protected function getAuthorizationUrl($issuerConfiguration, $identifier, $clientId, $state)
	{
		$authorizationUrl = Uri::getInstance($issuerConfiguration->get('authorization_endpoint'));
		$authorizationUrl->setVar('claims', urlencode($this->getUserInfoClaims($issuerConfiguration->get('claims_supported'))));
		$authorizationUrl->setVar('scope', 'openid');
		$authorizationUrl->setVar('response_type', 'code');
		$authorizationUrl->setVar('client_id', $clientId);
		$authorizationUrl->setVar('redirect_uri', urlencode($this->getValidateUrl()));
		$authorizationUrl->setVar('login_hint', $identifier);
		$authorizationUrl->setVar('state', $state);
		$authorizationUrl->setScheme($this->applicationType === 'web' ? 'https' : 'http');

		return $authorizationUrl->toString();
	}

	/**
	 * Returns the claims we request from the issuer
	 *
	 * @param   array  $claimsSupported  List of supportet claims by the issuer
	 *
	 * @return string  json encoded claims
	 *
	 * @since  1.0.0
	 */
	protected function getUserInfoClaims($claimsSupported)
	{
		return json_encode([
			'userinfo' => [
				'given_name' => ['essential' => true, 'reason' => Text::_('PLG_SYSTEM_ID4ME_CLAIM_REASON_GIVEN_NAME')],
				'email' => ['essential' => true, 'reason' => Text::_('PLG_SYSTEM_ID4ME_CLAIM_REASON_EMAIL')],
				'email_verified' => ['essential' => true, 'reason' => Text::_('PLG_SYSTEM_ID4ME_CLAIM_REASON_EMAILVERIFIED')],
			],
			'id_token' => [
				'auth_time' => ['essential' => true],
			]
		]);
	}

	/**
	 * Returns the validation URL
	 *
	 * @return   string  The validation URL
	 *
	 * @since   1.0.0
	 */
	protected function getValidateUrl()
	{
		return Uri::getInstance()
			->setQuery(self::$redirectValidateUrl)
			->setScheme($this->applicationType === 'web' ? 'https' : 'http')
			->toString();
	}

	/**
	 * Register our service to get the clientId and clientSecret
	 *
	 * @param   string  $registrationEndpoint The Registration endpoint URL
	 *
	 * @return  Registry An Registry object containing the result of the registration request
	 *
	 * @since  1.0.0
	 */
	protected function registerService($registrationEndpoint)
	{
		$registrationDataJSON = json_encode([
			'client_name'      => $this->app->get('sitename'),
			'application_type' => $this->applicationType,
			'redirect_uris'    => [$this->getValidateUrl()],
		]);

		$registrationResult = HttpFactory::getHttp()->post(
			$registrationEndpoint,
			$registrationDataJSON,
			['Content-Type' => 'application/json']
		);

		if (empty($registrationResult->body) || !in_array($registrationResult->code, ['200', '201']))
		{
			$this->dieHard();
		}

		return new Registry($registrationResult->body);
	}

	/**
	 * Try to read the issuer information from the DNS TXT Record.
	 *
	 * @param   string  $hostname  The identifier/Domain of the user to authenticate
	 *
	 * @return  string  Issuer URL
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
	 * @param   string  $identifier  The identifier/Domain of the user to authenticate
	 *
	 * @return  string  Issuer URL
	 *
	 * @since  1.0.0
	 */
	protected function getIssuerbyIdentifier($identifier)
	{
		$hostparts = explode('.', $identifier);
		$totalCountOfHostparts = count($hostparts);

		$i = 0;

		// Read the Identifier recursive and check who is the Issuer
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
	 * Return the OpenId Configuration from an given Issuer
	 *
	 * @param   string  $issuer  The Issuer URL
	 *
	 * @return  Registry The Registry object with the issuer configuration
	 *
	 * @since  1.0.0
	 */
	protected function getOpenIdConfiguration($issuer)
	{
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
