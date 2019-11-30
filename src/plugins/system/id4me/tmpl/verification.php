<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.ID4Me
 *
 * @copyright   Copyright (C) 2019 Benjamin Trenkle. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE
 */

defined('_JEXEC') or die;

?>
<script>window.console.log(window.opener);
if (window.opener && window.opener.Joomla && window.opener.Joomla.ID4Me && window.opener.Joomla.ID4Me.verification)
{
	window.opener.Joomla.ID4Me.verification('jform_id4me_identifier', 'jform_id4me_issuersub', {issuersub: '<?php echo htmlspecialchars($issuersub, ENT_QUOTES, 'UTF-8'); ?>'});

	window.self.close();
}
</script>