<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.ID4Me
 *
 * @copyright   Copyright (C) 2019 Benjamin Trenkle Wicked Software. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

require_once __DIR__ . '/libraries/vendor/autoload.php';

use Id4me\RP\Model\ClaimRequest;
use Id4me\RP\Model\ClaimRequestList;
use Id4me\RP\Service;
use Id4me\RP\Model\UserInfo;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserHelper;
use Joomla\CMS\Table\Table;
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
	 * The ID4Me object
	 *
	 * @var type
	 */
	protected static $id4me;

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
	 * Creates the ID4Me service
	 *
	 * @return Service
	 */
	protected function ID4MeHandler()
	{
		if (empty(self::$id4me))
		{
			require_once __DIR__ . '/libraries/id4mehttp.php';

			$http = new ID4MeHttp;

			self::$id4me = new Service($http);
		}

		return self::$id4me;
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
		$allowedLoginClient = (string) $this->params->get('allowed_login_client', 'site');

		// For now we hardcode site as only solution that is working
		$allowedLoginClient = 'site';

		if ((($allowedLoginClient === 'both' || $this->app->isClient($allowedLoginClient)) && Factory::getUser()->guest))
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

		$joomlaUser = $this->getJoomlaUserById4MeIdentifier();

		if ((!($joomlaUser instanceof User) || !$joomlaUser->id) && $this->getId4MeRegistrationEnabled() === false)
		{
			// We don't have an user associated to this identifier and we don't allow registration.
			$this->app->enqueueMessage(Text::sprintf('PLG_SYSTEM_ID4ME_NO_JOOMLA_USER_FOR_IDENTIFIER', $identifier), 'error');
			$this->app->redirect('index.php');

			return;
		}

		// Get the client form the current URL
		$requestedLoginClient = (string) Uri::getInstance()->getVar('client');
		$allowedLoginClient   = (string) $this->params->get('allowed_login_client', 'site');

		if ($allowedLoginClient != 'both' && $allowedLoginClient != $requestedLoginClient)
		{
			// We don't allow ID4me login to this client.
			$this->app->enqueueMessage(Text::sprintf('PLG_SYSTEM_ID4ME_NO_LOGIN_CLIENT', $requestedLoginClient), 'error');
			$this->app->redirect('index.php');

			return;
		}

		$this->app->setUserState('id4me.client', $requestedLoginClient);

		$authorityName = $this->ID4MeHandler()->discover($identifier);

		$openIdConfig = $this->ID4MeHandler()->getOpenIdConfig($authorityName);

		$client = $this->ID4MeHandler()->register($openIdConfig, $identifier, $this->getValidateUrl(), $this->applicationType);

		$state        = UserHelper::genRandomPassword(100);

		$this->app->setUserState('id4me.clientInfo', (object) $client);
		$this->app->setUserState('id4me.openIdConfig', $openIdConfig);
		$this->app->setUserState('id4me.state', $state);

        $authorizationUrl = $this->ID4MeHandler()->getAuthorizationUrl($openIdConfig, $client->getClientId(), $identifier, $client->getActiveRedirectUri(), $state, NULL,
            new ClaimRequestList(
                new ClaimRequest('given_name', true),
                new ClaimRequest('family_name', true),
                new ClaimRequest('name', true),
                new ClaimRequest('email', true)
            )
        );

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
		$code = $this->app->input->get('code');
		$client = $this->app->getUserState('id4me.clientInfo');
		$openIdConfig = $this->app->getUserState('id4me.openIdConfig');

		// Prevent __PHP_Incomplete_Class
		$client = unserialize(serialize($client));
		$openIdConfig = unserialize(serialize($openIdConfig));

		$authorizedAccessTokens = $this->ID4MeHandler()->getAuthorizationTokens($openIdConfig, $code, $client);

		$userInfo = $this->ID4MeHandler()->getUserInfo($openIdConfig, $client, $authorizedAccessTokens);

		$joomlaUser = $this->getJoomlaUserById4MeIdentifier();

		// The user does not exists than lets register him
		if ((!($joomlaUser instanceof User) || !$joomlaUser->id) && $this->getId4MeRegistrationEnabled())
		{
			$joomlaUser = $this->registerUser($userInfo);
		}

		$this->app->setUserState('id4me.identifier', '');

		$home = $this->app->getMenu()->getDefault();

		if ($joomlaUser instanceof User && $joomlaUser->id > 0)
		{
			// Load user plugins
			PluginHelper::importPlugin('user');

			// Login options
			$options = array(
				'autoregister' => false,
				'remember'     => false,
				'action'       => 'core.login.site',
				'redirect_url' => Route::_('index.php?Itemid=' . (int) $home->id, false),
			);

			$returnUrl = 'index.php';

			if ($this->app->getUserState('id4me.client') === 'administrator')
			{
				$options['action']       = 'core.login.admin';
				$options['group']        = 'Public Backend';
				$options['redirect_url'] = Route::_('index.php?option=com_cpanel', false);
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
				$this->app->redirect($options['redirect_url']);

				return;
			}
		}

		// We don't have an authorization URL so we can't do anything here.
		$this->app->enqueueMessage(Text::_('PLG_SYSTEM_ID4ME_LOGIN_FAILED'), 'error');
		$this->app->redirect(Route::_('index.php?Itemid=' . (int) $home->id, false));
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
			->where($this->db->quoteName('user_id') . ' = ' . $this->db->quote($data->id))
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
				->select('*')
				->from('#__user_profiles')
				->where($this->db->quoteName('user_id') . ' <> ' . (int) $data['id'])
				->where($this->db->quoteName('profile_key') . ' = ' . $this->db->quote('id4me.identifier'))
				->where($this->db->quoteName(['profile_value']) . ' = ' . $this->db->quote($identifier));

			$this->db->setQuery($query);

			try
			{
				$rows = (int) $this->db->getNumRows();
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

		$profile = new stdClass;

		$profile->user_id = (int) $userId;
		$profile->profile_key = 'id4me.identifier';
		$profile->profile_value = $identifier;
		$profile->ordering = 1;

		try
		{
			$this->db->insertObject('#__user_profiles', $profile);
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
			->from($this->db->quoteName('#__user_profiles'))
			->where($this->db->quoteName('profile_value') . ' = ' . $this->db->quote($this->app->getUserState('id4me.identifier')))
			->where($this->db->quoteName('profile_key') . ' = ' . $this->db->quote('id4me.identifier'));

		$this->db->setQuery($query);

		try
		{
			$userId = (int) $this->db->loadResult();
			$rows = (int) $this->db->getNumRows($this->db->execute());
		}
		catch (\RuntimeException $e)
		{
			$this->app->enqueueMessage(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), 'error');

			return false;
		}

		if ($rows > 1)
		{
			// For some reason we have more than one result this is an critial error
			$this->app->enqueueMessage(Text::_('PLG_SYSTEM_ID4ME_IDENTIFIER_NOT_AMBIGOUOUS'), 'error');

			return false;
		}

		return Factory::getUser($userId);
	}

	protected function registerUser(UserInfo $userInfo)
	{
		$params = ComponentHelper::getParams('com_users');

		$table = Table::getInstance('User', 'JTable');

		$identifier = $this->app->getUserState('id4me.identifier');

		$user = [
			'username' => $identifier,
			'email' => $userInfo->getEmailVerified() ?: $userInfo->getEmail(),
			'name' => $userInfo->getName() ?: $userInfo->getGivenName() . ' ' . $userInfo->getFamilyName()
		];

		$user['groups'] = [$params->get('new_usertype', $params->get('guest_usergroup', 1))];

		$result = $table->save($user);

		if ($result)
		{
			$profile = new stdClass;

			$profile->user_id = (int) $table->id;
			$profile->profile_key = 'id4me.identifier';
			$profile->profile_value = $identifier;
			$profile->ordering = 1;

			try
			{
				$this->db->insertObject('#__user_profiles', $profile);
			}
			catch (\RuntimeException $e)
			{
				$this->app->enqueueMessage(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), 'error');

				return false;
			}
		}

		return Factory::getUser($table->id);
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
		if (!is_numeric($id4MeRegistation))
		{
			return $comUsersRegistation;
		}

		// Id4Me registration is enabled and the global setting too.
		if ($id4MeRegistation > 0 && $comUsersRegistation)
		{
			return true;
		}

		// In all other cases we don't enable registration
		return false;
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
}
