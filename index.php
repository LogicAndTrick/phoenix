<?php

include 'Phoenix/Phoenix.php';

/*
 ****************************************************************************
 * REQUIRED SETTINGS
 * Phoenix will not work if you do not set the following
 ****************************************************************************
 */

// The name of the default controller
Router::$default_controller = 'Home';
// The name of the default action (for all controllers)
Router::$default_action = 'Index';

// The name of the default master page (can be changed in the controller)
Templating::$master_page = 'Shared/Master';

// The absolute path to the application directory on the server's file system
Phoenix::$app_dir = realpath(dirname(__FILE__)).'/App';
// The absolute path to the application directory on the website's domain
Phoenix::$base_url = '/';

// Defaults to false; Set to true to enable debug output
Phoenix::$debug = false;
// Set to a non-null value to limit debug output to a specific username only
Phoenix::$debug_user = null;

/*
 ****************************************************************************
 * DATABASE SETTINGS
 * Required to use the database. Phoenix still works without a database.
 ****************************************************************************
 */

// The database type to use, can be mysql or sqlite
Database::$type = 'mysql';
// All self explanatory
Database::$host = 'hostname';
Database::$database = 'database';
Database::$username = 'username';
Database::$password = 'password';

// Uncomment to enable the database
//Database::Enable();

// Add a logger to the database (optional)
//$elog = new EchoLogger();
//$elog->printstacktrace = false;
//Database::AddLogger($elog);

/*
 ****************************************************************************
 * AUTHENTICATION SETTINGS
 * If you want people to be able to 'log in' to your site, use
 * authentication. Note that authentication requires the database to
 * be enabled before you enable authentication.
 ****************************************************************************
 */

// Change the session variable that holds the ID of the logged in user
Authentication::$session_id = 'phoenix_login';
// If set to true, auth will log the user in when the fields are contained in the postback data
Authentication::$autologin = true;
// If set to true, openid will be enabled for registration and login
Authentication::$useopenid = true;
// If set to true, all new user accounts must have an email address
Authentication::$emailrequired = true;
// If set to true, all new user accounts will be sent an email with a verification link before they can log in
Authentication::$emailconfirmation = false;

// The class name of the model to use
Authentication::$model = 'User';
// The primary key of the user model
Authentication::$field_id = 'ID';
// The username field of the model
Authentication::$field_username = 'Name';
// The password field of the model
Authentication::$field_password = 'Password';
// The email field of the model
Authentication::$field_email = 'Email';
// The field to store the model's OpenID URL
Authentication::$field_openid = 'OpenID';
// Holds the cookie session key (can be null, but cookies will be disabled)
Authentication::$field_cookie = 'Cookie';
// The field to be incremented when a user logs in with cookies or postback (can be null)
Authentication::$field_numlogins = 'NumLogins';
// The field to update to the current time when a user logs in (can be null)
Authentication::$field_lastlogin = 'LastLogin';
// The field to update to the current time when a user accesses a page (can be null)
Authentication::$field_lastaccess = 'LastAccess';
// The field to update to the current route info when a user accesses a page (can be null)
Authentication::$field_lastpage = 'LastPage';
// The field to update to the current IP of the user when they log in (can be null)
Authentication::$field_ip = 'IP';
// The field containing the account unlocking verification code (cannot be null if using email confirmation)
Authentication::$field_unlocker = 'Unlock';

// The name of the cookie that holds the id of the logged in user (can be null, but cookies will be disabled)
Authentication::$cookie_id = 'phoenix_username';
// The name of the cookie that holds the cookie session key (can be null, but cookies will be disabled)
Authentication::$cookie_code = 'phoenix_code';
// The time before the cookies will expire (cookies will be disabled if not > 0)
Authentication::$cookie_timeout = 30000000;

// The name of the username post field to use when logging in or creating an account (cannot be null if using autologin or account creation)
Authentication::$post_username = 'username';
// The name of the password post field to use when logging in or creating an account (cannot be null if using autologin or account creation)
Authentication::$post_password = 'password';
// The name of the password confirmation post field to use when creating an account (cannot be null if using account creation)
Authentication::$post_password_confirm = 'password_confirm';
// The name of the 'remember me' post field to use when logging in (can be null, autologin will assume a false value)
Authentication::$post_remember = 'remember';
// The name of the logout post field to use when logging out (can be null, autologin will still work but autologout will not)
Authentication::$post_logout = 'logout';
// The name of the email post field to use when creating an account (cannot be null if using account creation)
Authentication::$post_email = 'email';
// The name of the openid post field to use when creating an account (cannot be null if using account creation)
Authentication::$post_openid = 'openid';
// The name of the register type post field to use when creating an account (cannot be null if using account creation)
Authentication::$post_register = 'register';

// Fields that must be user-populated. This is the email template that will be sent when using email confirmation for creating accounts
// {username} will be replaced with the user's username
// {id} is replaced with the user's id,
// {unlock_code} will be the user's unlock code

// Email's 'nice' from name
Authentication::$email_from_name = 'User Verification';
// Email's actual from address
Authentication::$email_from_email = 'noreply@example.com';
// Email subject
Authentication::$email_subject = 'User Account Verification';
// Email content
Authentication::$email_content = "Hi {username},\r\n" .
                                 "Your account has been created. To enable your account you must click the following link.\r\n" .
                                 "http://example.com/Account/Verify/{id}/{unlock_code}";
// When set to true, sendmail's '-f' parameter will be used in PHP's mail() function - this helps to avoid a lot of spam filters.
Authentication::$email_sendmail_envelope = true;

// Register a password hasher. IMPORTANT: CHANGE THE SALT VALUES!
Authentication::RegisterPasswordHasher(new AlgorithmHasher('sha256', 'SALT_PREPEND', 'SALT_APPEND'));

// Uncomment to enable authentication
//Authentication::Enable();

/*
 ****************************************************************************
 * AUTHORISATION SETTINGS
 * If you have authentication set up, chances are you want authorisation.
 * This enables you to limit certain pages to certain users based on a
 * permissions system of some sort.
 ****************************************************************************
 */

// Use an authorisation method by setting the method like so:
//Authorisation::SetAuthorisationMethod(new DefaultAuthorisation());

/*
 ****************************************************************************
 * END USER CONFIGURATION
 ****************************************************************************
 */

// Run the framework. Removing this will make your website do nothing.
Phoenix::Run();

?>
