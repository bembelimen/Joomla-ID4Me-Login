var Joomla = window.Joomla || {};

(function(document, Joomla)
{
  document.addEventListener('DOMContentLoaded', function()
  {
    Joomla.ID4Me = function(element, options)
    {
      var forms = [].slice.call(document.querySelectorAll(element));

      var buttonimage = options && options.buttonimage ? options.buttonimage : '/media/plg_system_id4me/images/id4me-login-button.svg';
      var loginimage = options && options.loginimage ? options.loginimage : '/media/plg_system_id4me/images/id4me-start-login1.svg';

      var options = Object.assign({
        template: '<div class="id4me-wrapper">' +
                    '<form class="id4me-form" method="post" action="' + (options.formAction || '') + '">' +
                      '<div class="id4me-fields id4me-hide row-fluid">' +
                        '<label>' + Joomla.JText._('PLG_SYSTEM_ID4ME_IDENTIFIER_LABEL') + '</label>' +
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
    }
  });
})(document, Joomla);
