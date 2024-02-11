<?
// WordPress User Registration Hook
function my_custom_user_registration_function($user_id) {
    $user_info = get_userdata($user_id);
    $username = $user_info->user_login;
    $email = $user_info->user_email;
    $first_name = $user_info->first_name;
    $last_name = $user_info->last_name;

    // Automatically generate password
    $password = wp_generate_password();

    // Set a password for the user
    wp_set_password($password, $user_id);

    // Check if the user exists in Moodle
    $moodle_user_exists = check_user_exists_in_moodle($username);

    if ($moodle_user_exists) {
        // If the user exists in Moodle, auto-login using Moodle Authentication API
        auto_login_user_in_moodle($username, $password);
    } else {
        // If the user doesn't exist in Moodle, create the user and auto-login
        $first_name = $user_info->first_name; // Modify this based on your registration form
        $last_name = $user_info->last_name;   // Modify this based on your registration form
        $user_password = $password; // Use the provided password

        // Trigger user registration webhook to create the user in Moodle
        trigger_user_registration_webhook($username, $email, $password, $first_name, $last_name);
    }
}

add_action('user_register', 'my_custom_user_registration_function', 10, 1);

// WordPress Webhook and User Registration Webhook Callback
function register_user_webhook() {
    register_rest_route('my/v1', '/user-registration', array(
        'methods' => 'POST',
        'callback' => 'handle_user_registration_webhook',
    ));
}

add_action('rest_api_init', 'register_user_webhook');

function handle_user_registration_webhook($data) {
    create_user_in_moodle($data['username'], $data['email'], $data['password'], $data['first_name'], $data['last_name']);
    error_log('User registration webhook payload: ' . print_r($data, true));
    return new WP_REST_Response('Webhook received successfully', 200);
}

// WordPress to Moodle API Call
function create_user_in_moodle($username, $email, $password, $first_name, $last_name) {
    $moodle_api_endpoint = 'https://my.moodle.com/webservice/rest/server.php';
    $moodle_api_token = 'enter-moodle-token-here';

    $hashed_password = wp_hash_password($password);

    $request_params = array(
        'wstoken' => $moodle_api_token,
        'wsfunction' => 'core_user_create_users',
        'moodlewsrestformat' => 'json',
        'users' => array(
            array(
                'username' => $username,
                'password' => $hashed_password,
                'firstname' => $first_name,
                'lastname' => $last_name,
                'email' => $email,
            ),
        ),
    );

    $response = wp_remote_post($moodle_api_endpoint, array(
        'body' => json_encode($request_params),
        'headers' => array('Content-Type' => 'application/json'),
    ));

    if (!is_wp_error($response)) {
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        $moodleUserId = $result[0]['id'];
        auto_login_user_in_moodle($moodleUserId, $username, $password);
    } else {
        error_log('Error creating user in Moodle: ' . $response->get_error_message());
    }
}

// Moodle Auto-login
function auto_login_user_in_moodle($moodleUserId, $moodleUsername, $moodlePassword) {
    $moodle_auth_endpoint = 'https://my.moodle.com/login/token.php';
    $moodle_api_token = 'enter-moodle-token-here';

    $auth_params = array(
        'username' => $moodleUsername,
        'password' => $moodlePassword,
        'service' => 'moodle_mobile_app',
    );

    $response = wp_remote_post($moodle_auth_endpoint, array(
        'body' => $auth_params,
        'headers' => array('Content-Type' => 'application/x-www-form-urlencoded'),
    ));

    if (!is_wp_error($response)) {
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        if (isset($result['token'])) {
            setcookie('MoodleAutoLoginToken', $result['token'], time() + 3600, '/');
            wp_redirect('https://my.moodle.com/');
            exit();
        } else {
            error_log('Error authenticating user in Moodle. Token not found in the response: ' . print_r($result, true));
        }
    } else {
        error_log('Error authenticating user in Moodle. HTTP Error: ' . print_r($response, true));
    }
}

// Helper function to check if the user exists in Moodle
function check_user_exists_in_moodle($username) {
    $moodle_api_endpoint = 'https://my.moodle.com/webservice/rest/server.php';
    $moodle_api_token = 'enter-moodle-token-here';

    // Settings for the Moodle API call to check for user existence
    $request_params = array(
        'wstoken' => $moodle_api_token,
        'wsfunction' => 'core_user_get_users',
        'moodlewsrestformat' => 'json',
        'criteria' => array(
            array(
                'key' => 'username',
                'value' => $username,
            ),
        ),
    );

    // Call the Moodle API to check for user existence
    $response = wp_remote_post($moodle_api_endpoint, array(
        'body' => json_encode($request_params),
        'headers' => array('Content-Type' => 'application/json'),
    ));

    // Check if the call was successful
    if (!is_wp_error($response)) {
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        // Check if there is a user with that username
        return !empty($result);
    } else {
        // Log the error
        error_log('Error checking user existence in Moodle: ' . $response->get_error_message());
        return false;
    }
}

// Helper function to use the user-provided password
function get_user_provided_password() {
    // Assume that the user enters the password via the registration form
    // Adjust depending on your registration system
    if (isset($_POST['password'])) {
        // Clean and sanitize the password
        return sanitize_text_field($_POST['password']);
    }
    // If the password is not provided or the user is not using the registration form,
    // you can adjust based on your system
    return ''; // Placeholder, replace with real logic if needed
}

// When calling the function to create a user in the Moodle system,
// use get_user_provided_password() to get the user-entered password

// Example of usage in the create_user_in_moodle() function:
$password = get_user_provided_password();
// Then pass $password as an argument to the create_user_in_moodle() function
