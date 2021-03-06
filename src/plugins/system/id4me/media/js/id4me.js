/**
 * Handle all JS actions
 *
 * @copyright   Copyright (C) 2019 Benjamin Trenkle. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE
 */

var Joomla = window.Joomla || {};

(function(document, Joomla)
{
  document.addEventListener('DOMContentLoaded', function()
  {
    Joomla.ID4Me = {};

	Joomla.ID4Me.login = function(element, options)
    {
      var forms = [].slice.call(document.querySelectorAll(element));

      var buttonimage = options && options.buttonimage ? options.buttonimage : '/media/plg_system_id4me/images/id4me-login-button.svg';
      var loginimage = options && options.loginimage ? options.loginimage : '/media/plg_system_id4me/images/id4me-start-login1.svg';

      var options = Object.assign({
        template: '<div class="id4me-wrapper">' +
                    '<form class="id4me-form" method="post" action="' + (options.formAction || '') + '">' +
                      '<div class="id4me-fields id4me-hide row-fluid">' +
                        '<label>' + Joomla.JText._('PLG_SYSTEM_ID4ME_LOGIN_LABEL') + '</label>' +
                        '<input required name="id4me-identifier" type="text" class="span12">' +
                        '<input type="image" src="' + loginimage + '" class="id4me-login">' +
                        (options.token ? options.token : '') +
                        '<input type="hidden" name="context" value="' + (options.context == 'admin' ? 'admin' : 'site') + '">' +
                      '</div>' +
                    '</form>' +
                    '<img src="' + buttonimage + '" alt="" class="id4me-button">' +
                  '</div>',
        buttonClass: 'id4me-button',
        loginClass: 'id4me-login',
        hideClass: 'id4me-hide',
      }, options);

      var appendID4MeButton = function(form)
      {
        form.insertAdjacentHTML('afterend', options.template);

        var id4me = form.nextElementSibling,
            button = id4me.querySelector('.' + options.buttonClass);

        button.addEventListener('click', function()
        {
          [].slice.call(id4me.querySelectorAll('.' + options.hideClass)).forEach(function(elem)
          {
            elem.classList.remove(options.hideClass);
          });

          form.classList.add(options.hideClass);
          button.classList.add(options.hideClass);
        })
      }

      forms.forEach(function(form)
      {
        appendID4MeButton(form);
      });
    };

	Joomla.ID4Me.profile = function(element, verification, options)
	{
	  var field = document.getElementById(element);
      var issuersub = document.getElementById(verification);

      var isvalid = !!(field.value.length && issuersub.value.length);

      var options = Object.assign({
        template: '<div class="input-append">' +
                    '{input}' +
                    '<button type="button" class="id4me-button btn btn-info' + (isvalid ? ' id4me-hide' : '') + '">Verify</button>' +
                    '<div class="add-on id4me-addon' + (!isvalid ? ' id4me-hide' : '') + '"><span class="text-success icon-ok"></span></div>' +
                  '</div>',
        buttonClass: 'id4me-button',
        addonClass: 'id4me-addon',
        hideClass: 'id4me-hide',
      }, options);

      var template = options.template;

      field.insertAdjacentHTML('afterend', template.replace('{input}', field.outerHTML));

      field.parentNode.removeChild(field);

      field = document.getElementById(element);

      var wrapper = field.parentNode;

      if (!field.value.length)
      {
        wrapper.querySelector('.' + options.buttonClass).classList.add(options.hideClass);
      }

      field.addEventListener('change', function()
      {
        if (field.value.length)
        {
          wrapper.querySelector('.' + options.buttonClass).classList.remove(options.hideClass);
        }
        else
        {
          wrapper.querySelector('.' + options.buttonClass).classList.add(options.hideClass);
        }

        wrapper.querySelector('.' + options.addonClass).classList.add(options.hideClass);

        issuersub.value = '';
      });

      wrapper.querySelector('.' + options.buttonClass).addEventListener('click', function()
      {
        var popupoptions = 'width=660,height=620,scrollbars=yes,top=' + (screen.height / 2 - 620 / 2) + ',left=' + (screen.width / 2 - 660 / 2);

        open(options.formAction + '&id4me-identifier=' + field.value, 'id4me-verification', popupoptions);
      });
	};

	Joomla.ID4Me.verification = function(element, verification, options)
	{
      var issuersub = options.issuersub || '';

      var options = Object.assign({
        buttonClass: 'id4me-button',
        addonClass: 'id4me-addon',
        hideClass: 'id4me-hide',
      }, options);

	  var field = document.getElementById(element),
          wrapper = field.parentNode;

      document.getElementById(verification).value = issuersub;

      wrapper.querySelector('.' + options.buttonClass).classList.add(options.hideClass);
      wrapper.querySelector('.' + options.addonClass).classList.remove(options.hideClass);
	};
  });
})(document, Joomla);
