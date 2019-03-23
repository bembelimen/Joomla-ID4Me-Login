## Process where this feature works 
- ID4ME Login Button is injected in the login form or as seperate module
- After clicking the button you enter the identifier
- The plugin checks whether the users with that login domain exitsts

### User does not exists
- load the DNS record and get the issuer domain
- forward to the issuer domain
-- user authenticat against the issuer --
- validate the response of the issuer
- create the Joomla User with an empty password, username (== identifier)
- user gets authenticated

### User does exists
- load the DNS record and get the issuer domain
- forward to the issuer domain
- validate the response of the issuer
- user gets authenticated
