<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';
require '../includes/DBOperations.php';

$app = AppFactory::create();

$app->addBodyParsingMiddleware();
$middleware = $app->addErrorMiddleware(true, true, true);

/**
 * endpoint: createuser
 * params: email, name, password, school
 * method: POST
 */
$app->post('/MyApi/public/createuser', function (Request $request, Response $response) {

    if (!haveEmptyParams(array('email', 'password', 'name', 'school'), $request, $response)) {
        $request_data = $request->getParsedBody();
        $email = $request_data['email'];
        $password = $request_data['password'];
        $name = $request_data['name'];
        $school = $request_data['school'];

        $hash_password = password_hash($password, PASSWORD_DEFAULT);

        $db = new DBOperations;

        $result = $db->createUser($email, $hash_password, $name, $school);

        if ($result == USER_CREATED) {
            $message = array();
            $message['error'] = false;
            $message['message'] = 'User created successfully';

            $response->getBody()->write(json_encode($message));

            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(201);
        } else if ($result == USER_FAILURE) {
            $message = array();
            $message['error'] = true;
            $message['message'] = 'Internal Error Occured';

            $response->getBody()->write(json_encode($message));

            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(422);
        } else if ($result == USER_EXISTS) {
            $message = array();
            $message['error'] = false;
            $message['message'] = 'User already exists';

            $response->getBody()->write(json_encode($message));

            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(422);
        }
    } else {
        return $response
            ->withHeader('Content-type', 'application/json')
            ->withStatus(422);
    }
});

/**
 * endpoint: userlogin
 * params: email, password
 * method: POST
 */
$app->post('/MyApi/public/userlogin', function (Request $request, Response $response) {
    if (!haveEmptyParams(array('email', 'password'), $request, $response)) {
        $request_data = $request->getParsedBody();

        $email = $request_data['email'];
        $password = $request_data['password'];

        $db = new DBOperations;

        $result = $db->userLogin($email, $password);

        if ($result == USER_AUTHENTICATED) {
            $user = $db->getUserByEmail($email);

            $response_details = array();
            $response_details['error'] = false;
            $response_details['message'] = 'Login Successful';
            $response_details['user'] = $user;

            $response->getBody()->write(json_encode($response_details));

            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(200);
        } else if ($result == USER_NOT_FOUND) {

            $response_details = array();
            $response_details['error'] = true;
            $response_details['message'] = 'User do not exists';

            $response->getBody()->write(json_encode($response_details));

            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(404);
        } else if ($result == USER_PASSWORD_DO_NOT_MATCH) {

            $response_details = array();
            $response_details['error'] = true;
            $response_details['message'] = 'Wrong Password';

            $response->getBody()->write(json_encode($response_details));

            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(404);
        }
    } else {
        return $response
            ->withHeader('Content-type', 'application/json')
            ->withStatus(422);
    }
});

/**
 * endpoint: allusers
 * params: none
 * method: GET
 */
$app->get('/MyApi/public/allusers', function (Request $request, Response $response) {
    $db = new DBOperations;

    $users = $db->getAllUsers();

    $response_details = array();
    $response_details['error'] = false;
    $response_details['users'] = $users;

    $response->getBody()->write(json_encode($response_details));

    return $response
        ->withHeader('Content-type', 'application/json')
        ->withStatus(200);
});

/**
 * endpoint: updateuser
 * params: email, name, school
 * args: id
 * method: PUT
 */
$app->put('/MyApi/public/updateuser/{id}', function (Request $request, Response $response, array $args) {
    $id = $args['id'];

    if (!haveEmptyParams(array('email', 'school', 'name'), $request, $response)) {

        $request_data = $request->getParsedBody();

        $email = $request_data['email'];
        $name = $request_data['name'];
        $school = $request_data['school'];

        $db = new DBOperations;

        if ($db->updateUser($name, $email, $school, $id)) {
            $user = $db->getUserByEmail($email);

            $response_details = array();
            $response_details['error'] = false;
            $response_details['message'] = 'Updated Successful';
            $response_details['user'] = $user;

            $response->getBody()->write(json_encode($response_details));

            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(200);
        } else {
            $response_details['error'] = true;
            $response_details['message'] = 'Updated Failed';

            $response->getBody()->write(json_encode($response_details));

            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(422);
        }
    } else {
        return $response
            ->withHeader('Content-type', 'application/json')
            ->withStatus(422);
    }
});

/**
 * endpoint: updatepassword
 * params: password
 * method: PUT
 */
$app->put('/MyApi/public/updatepassword', function (Request $request, Response $response) {

    if (!haveEmptyParams(array('new_password', 'current_password', 'email'), $request, $response)) {

        $request_data = $request->getParsedBody();

        $newPassword = $request_data['new_password'];
        $email = $request_data['email'];
        $currentPassword = $request_data['current_password'];


        $db = new DBOperations;
        $result = $db->updateUserPassword($newPassword, $currentPassword, $email);

        if ($result == PASSWORD_CHANGED) {

            $response_details = array();
            $response_details['error'] = false;
            $response_details['message'] = 'Password Updated Successful';

            $response->getBody()->write(json_encode($response_details));

            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(200);
        } else if ($result == PASSWORD_NOT_CHANGED) {
            $response_details['error'] = true;
            $response_details['message'] = 'Password Updated Failed';

            $response->getBody()->write(json_encode($response_details));

            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(422);
        } else if ($result == PASSWORD_DO_NOT_MATCH) {
            $response_details['error'] = true;
            $response_details['message'] = 'Password do not match';

            $response->getBody()->write(json_encode($response_details));

            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(422);
        }
    } else {
        return $response
            ->withHeader('Content-type', 'application/json')
            ->withStatus(422);
    }
});


function haveEmptyParams($params, $request, $response)
{
    $error = false;
    $error_params = '';
    $request_params = $request->getParsedBody();

    foreach ($params as $param) {
        if (!isset($request_params[$param]) || strlen($request_params[$param]) <= 0) {
            $error = true;
            $error_params .= $param . ', ';
        }
    }

    if ($error) {
        $error_details = array();
        $error_details['error'] = true;
        $error_details['message'] = 'Required parameters ' . substr($error_params, 0, -2) . ' are missing.';
        $response->getBody()->write(json_encode($error_details));
    }

    return $error;
}

$app->run();
