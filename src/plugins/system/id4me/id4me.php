<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.ID4Me
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserHelper;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;

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
	 * Database object.
	 *
	 * @var    DatabaseDriver
	 * @since  3.8.0
	 */
	protected $db;

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
	static $redirectValidateUrl = 'option=com_ajax&plugin=ID4MeLogin&format=raw';

	/**
	 * The url used to trigger the login request to the identity provider
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	static $formActionLoginUrl = 'index.php?option=com_ajax&plugin=ID4MePrepare&format=raw&client={client}';

	/**
	 * The user edit form contexts
	 *
	 * @var    array
	 * @since  1.0.0
	 */
	protected $supportedContext = array(
		'com_users.profile',
		'com_users.user',
		'com_admin.profile',
	);

	/**
	 * Constructor
	 *
	 * @param   object  &$subject  The object to observe
	 * @param   array   $config    An array that holds the plugin configuration
	 *
	 * @since   1.0.0
	 */
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);

		// When we are at locahost we need to set the application type to native
		if (substr(Uri::getInstance()->toString(), 0, 17) === 'http://localhost/' 
			|| substr(Uri::getInstance()->toString(), 0, 18) === 'https://localhost/')
		{
			$this->applicationType = 'native';
		}
	}

	/**
	 * Using this event we add our JaveScript and CSS code to add the id4me button to the login form.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function onBeforeRender()
	{
		if (($this->app->isClient('site')
			|| ($this->app->isClient('administrator') && Factory::getUser()->guest))
			&& (NULL === $this->app->getUserState('id4me.identifier')))
		{
			// Load the language string
			Text::script('PLG_SYSTEM_ID4ME_IDENTIFIER_LABEL');

			// Load the layout with the JaveScript and CSS
			echo $this->loadLayout('login');
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

		if ($this->getJoomlaUserById4MeIdentifier() === false && $this->getId4MeRegistrationEnabled() === false)
		{
			// We don't have an user associated to this identifier and we don't allow registration.
			$this->app->enqueueMessage(Text::sprintf('PLG_SYSTEM_ID4ME_NO_JOOMLA_USER_FOR_IDENTIFIER', $identifier), 'error');
			$this->app->redirect('index.php');

			return;
		}

		// Get the client form the current URL
		$this->app->setUserState('id4me.client', Uri::getInstance()->getVar('client'));

		// Get Issuer
		$issuer = Uri::getInstance($this->getIssuerbyIdentifier($identifier));
		$issuer->setScheme('https');

		$issuerConfiguration  = $this->getOpenIdConfiguration($issuer->toString());
		$registrationEndpoint = (string) $issuerConfiguration->get('registration_endpoint');
		$registrationResult   = $this->registerService($registrationEndpoint);

		$clientId     = $registrationResult->get('client_id');
		$clientSecret = $registrationResult->get('client_secret');
		$state        = UserHelper::genRandomPassword(100);

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
		$issuer = Uri::getInstance(
			$this->getIssuerbyIdentifier(
				$this->app->getUserState('id4me.identifier')
			)
		);

		$issuer->setScheme('https');
		
		$issuerConfiguration = $this->getOpenIdConfiguration($issuer->toString());

		$bearerToken = $this->validateAuthTokens(
			$this->getAuthTokens(
				$this->app->input->get('code'),
				$issuerConfiguration->get('token_endpoint'),
				$this->app->getUserState('id4me.client_id'),
				$this->app->getUserState('id4me.client_secret')
			)
		);

		$joomlaUser = $this->getJoomlaUserById4MeIdentifier();

		if ($joomlaUser instanceof User)
		{
			// Load user plugins
			PluginHelper::importPlugin('user');

			// Login options
			$options = array(
				'autoregister' => false,
				'remember'     => false,
				'action'       => 'core.login.site',
			);

			$returnUrl = 'index.php';

			if ($this->app->getUserState('id4me.client') === 'administrator')
			{
				$options['action'] = 'core.login.admin';
				$returnUrl         = Uri::root() . 'administrator/index.php';
			}

			// OK, the credentials are authenticated and user is authorised. Let's fire the onLogin event.
			$results = $this->app->triggerEvent('onUserLogin', array((array) $joomlaUser, $options));

			/*
			 * If any of the user plugins did not successfully complete the login routine
			 * then the whole method fails.
			 *
			 * Any errors raised should be done in the plugin as this provides the ability
			 * to provide much more information about why the routine may have failed.
			 */
			if (in_array(false, $results, true) == false)
			{
				$options['user'] = Factory::getUser();
				$options['responseType'] = 'id4me';

				// The user is successfully logged in. Run the after login events
				$this->app->triggerEvent('onUserAfterLogin', array($options));
				$this->app->redirect($returnUrl);

				return;
			}

			// We don't have an authorization URL so we can't do anything here.
			$this->app->enqueueMessage(Text::_('PLG_SYSTEM_ID4ME_LOGIN_FAILED'), 'error');
			$this->app->redirect($returnUrl);

			return;
		}

		// The user does not exists than lets register him
		if ($this->getId4MeRegistrationEnabled())
		{
			$claims = $this->getClaims($issuerConfiguration->get('userinfo_endpoint'), $bearerToken);
		}
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
	 * Add id4me field to the user edit form
	 *
	 * @param   JForm  $form  The form to be altered.
	 * @param   mixed  $data  The associated data for the form.
	 *
	 * @return  boolean
	 *
	 * @since   1.0.0
	 */
	public function onContentPrepareForm(JForm $form, $data)
	{
		// Check for the user edit forms
		if (!in_array($form->getName(), $this->supportedContext))
		{
			return true;
		}

		$form->load('
			<form>
				<fieldset name="id4me" label="PLG_SYSTEM_ID4ME_FIELDSET_LABEL">
					<field
						name="id4me_identifier"
						type="text"
						label="PLG_SYSTEM_ID4ME_IDENTIFIER_LABEL"
						description="PLG_SYSTEM_ID4ME_IDENTIFIER_DESC"
					/>
				</fieldset>
			</form>'
		);
	}

	/**
	 * Runs on content preparation
	 *
	 * @param   string  $context  The context for the data
	 * @param   object  $data     An object containing the data for the form.
	 *
	 * @return  boolean
	 *
	 * @since   1.6
	 */
	public function onContentPrepareData($context, $data)
	{
		// Check for the user edit forms
		if (!in_array($context, $this->supportedContext))
		{
			return true;
		}

		$query = $this->db->getQuery(true)
			->select($this->db->quoteName(['profile_value']))
			->from('#__user_profiles')
			->where($this->db->quoteName('user_id') . ' = ' . Factory::getUser()->id)
			->where($this->db->quoteName('profile_key') . ' = ' . $this->db->quote('id4me.identifier'));

		$this->db->setQuery($query);

		try
		{
			$data->id4me_identifier = (string) $this->db->loadResult();
		}
		catch (\RuntimeException $e)
		{
			// We can not read the field but this is not critial the field will just be empty
		}
	}

	/**
	 * Method is called before user data is stored in the database
	 *
	 * @param   array    $user   Holds the old user data.
	 * @param   boolean  $isnew  True if a new user is stored.
	 * @param   array    $data   Holds the new user data.
	 *
	 * @return  boolean
	 * @throws  InvalidArgumentException When the ID4me Identifier is already assigned to another user.
	 *
	 * @since   1.0.0
	 */
	public function onUserBeforeSave($user, $isnew, $data)
	{
		$identifier = $data['id4me_identifier'];
		
		if (!empty($identifier))
		{
			$query = $this->db->getQuery(true)
				->select($this->db->quoteName(['profile_value']))
				->from('#__user_profiles')
				->where($this->db->quoteName('user_id') . ' <> ' . (int) $data['id'])
				->where($this->db->quoteName('profile_key') . ' = ' . $this->db->quote('id4me.identifier'));

			$this->db->setQuery($query);

			try
			{
				$rows = (array) $this->db->loadObjectList();
			}
			catch (\RuntimeException $e)
			{
				$this->app->enqueueMessage(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), 'error');

				return false;
			}

			foreach ($rows as $row)
			{
				if ($row->profile_value === $identifier)
				{
					// The identifier is already used
					throw new InvalidArgumentException(Text::sprintf('PLG_SYSTEM_ID4ME_IDENTIFIER_ALREADY_USED', $identifier));

					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Saves user id4me data to the database
	 *
	 * @param   array    $data    entered user data
	 * @param   boolean  $isNew   true if this is a new user
	 * @param   boolean  $result  true if saving the user worked
	 * @param   string   $error   error message
	 *
	 * @return  boolean
	 *
	 *  @since   1.0.0
	 */
	public function onUserAfterSave($data, $isNew, $result, $error)
	{
		$userId     = ArrayHelper::getValue($data, 'id', 0, 'int');
		$identifier = $data['id4me_identifier'];

		$query = $this->db->getQuery(true)
			->delete($this->db->quoteName('#__user_profiles'))
			->where($this->db->quoteName('user_id') . ' = ' . (int) $userId)
			->where($this->db->quoteName('profile_key') . ' = ' . $this->db->quote('id4me.identifier'));
		$this->db->setQuery($query);

		try
		{
			$this->db->execute();
		}
		catch (\RuntimeException $e)
		{
			$this->app->enqueueMessage(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), 'error');

			return false;
		}

		$query = $this->db->getQuery(true)
			->insert($this->db->quoteName('#__user_profiles'))
			->columns($this->db->quoteName(array('user_id', 'profile_key', 'profile_value', 'ordering')))
			->values(implode(',', array($userId, $this->db->quote('id4me.identifier'), $this->db->quote($identifier), 1)));
		$this->db->setQuery($query);

		try
		{
			$this->db->execute();
		}
		catch (\RuntimeException $e)
		{
			$this->app->enqueueMessage(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), 'error');

			return false;
		}

		return true;
	}

	/**
	 * Get the Joomla User by Id4Me Identifier
	 *
	 * @return  mixed  Returns the Joomla User for the Id4Me Identifier or false in case there is no user associated 
	 *
	 * @since   1.0.0
	 */
	protected function getJoomlaUserById4MeIdentifier()
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName(['user_id']))
			->from('#__user_profiles')
			->where($this->db->quoteName('profile_value') . ' = ' . $this->db->quote($this->app->getUserState('id4me.identifier')))
			->where($this->db->quoteName('profile_key') . ' = ' . $this->db->quote('id4me.identifier'));

		$this->db->setQuery($query);

		try
		{
			$rows = (array) $this->db->loadColumn();
		}
		catch (\RuntimeException $e)
		{
			$this->app->enqueueMessage(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), 'error');

			return false;
		}

		if (count($rows) > 1)
		{
			// For some reason we have more than one result this is an critial error
			$this->app->enqueueMessage(Text::_('PLG_SYSTEM_ID4ME_IDENTIFIER_NOT_AMBIGOUOUS'), 'error');

			return false;
		}

		try
		{
			$userId = (int) $this->db->loadResult();
		}
		catch (\RuntimeException $e)
		{
			$this->app->enqueueMessage(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), 'error');

			return false;
		}
			
		return Factory::getUser($userId);
	}

	/**
	 * Check if the id4me registation is allowed or not
	 *
	 * @return  bool  True if the registration is enabeld false in other cases
	 *
	 * @since   1.0.0
	 */
	protected function getId4MeRegistrationEnabled()
	{
		// Get the global value as boolean
		$comUsersRegistation = (bool) ComponentHelper::getParams('com_users')->get('allowUserRegistration', 0);

		// Get the plugin configuration
		$id4MeRegistation = $this->params->get('allow_registration', '');

		// We are in `Use Global` mode so lets return the global setting
		if (empty($id4MeRegistation))
		{
			return $comUsersRegistation;
		}

		// Id4Me registration is enabled and the global setting too.
		if ($id4MeRegistation === '1' || $comUsersRegistation)
		{
			return true;
		}

		// In all other cases we don't enable registration
		return false;
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
		$validateUrl = Uri::getInstance();
		$validateUrl->setQuery(self::$redirectValidateUrl);
		$validateUrl->setScheme($this->applicationType === 'web' ? 'https' : 'http');

		return $validateUrl->toString();
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
