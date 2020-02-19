<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.ID4Me
 *
 * @copyright   Copyright (C) 2019 Benjamin Trenkle Wicked Software. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE
 */

defined('_JEXEC') or die;

require_once __DIR__ . '/vendor/autoload.php';

use Id4me\RP\HttpClient;
use Joomla\CMS\Http\Http;
use Joomla\CMS\Http\HttpFactory;

class ID4MeHttp implements HttpClient
{
	protected $http;

	public function get($url, array $headers = array())
	{
		$http = $this->getHttp();

		return $http->get($url, $headers)->body;
	}

	public function post($url, $body, array $headers = array())
	{
		$http = $this->getHttp();

		return $http->post($url, $body, $headers)->body;
	}

	/**
	 * Single instance loading of http
	 *
	 * @return  Http      Joomla Http class
	 *
	 * @throws  \RuntimeException
	 */
	protected function getHttp()
	{
		if (empty($this->http))
		{
			$this->http = HttpFactory::getHttp();
		}

		return $this->http;
	}

}
