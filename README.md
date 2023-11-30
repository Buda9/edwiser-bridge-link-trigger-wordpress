# Edwiser Bridge 3.x Extender - User Registered Auto-linker with Moodle

## Functionality Description:

This WordPress plugin facilitates integration between the WordPress platform and the Moodle system. Key functionalities include:

1. **Automatic User Registration:** When a new user registers on WordPress, the plugin automatically checks if a user with the same credentials exists in the Moodle system. If not, it creates a new user in Moodle.

2. **Automatic Moodle Login:** If the user already exists in the Moodle system, the plugin automatically logs in the user to Moodle after a successful registration on WordPress.

3. **Webhook Support:** Implements a WordPress webhook to track registration events. When a user registers on WordPress, the plugin sends registration information via the webhook.

4. **Secure Data Exchange:** Ensures secure data exchange between WordPress and Moodle through API calls, including the use of tokens for authentication.

5. **Customizable Settings:** Allows customization of key settings such as Moodle API endpoint, tokens, and other information that may vary depending on the Moodle installation.

## How to Use:

1. Insert code in your WordPress theme functions.php file
2. Configure key parameters such as Moodle API token, endpoint, etc.
3. The plugin will automatically integrate user registration on WordPress with the Moodle

TODO:
[x] Automatic User Registration 
[ ] Automatic Moodle Login (needs brighter mind to help me with this)
[x] Webhook Support
[x] Secure Data Exchange
[x] Customizable Settings
