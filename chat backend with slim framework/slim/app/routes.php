<?php

declare(strict_types=1);

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;


return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });




    // initializeUserDatabase: initializes the users.db to store the usernames
    function initializeUserDatabase()
    {
        try {
            $pdo = new \PDO('sqlite:users.db');
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $createTableQuery = 'CREATE TABLE IF NOT EXISTS users (
                                name TEXT PRIMARY KEY
                            );';
            $pdo->exec($createTableQuery);
            return 'Users table initialized successfully.';
        } catch (\PDOException $e) {
            return 'Error: ' . $e->getMessage();
        }
    }


    $app->get('/', function (Request $request, Response $response) {
        // Initialize the user database
        $initResult = initializeUserDatabase();

        // Project started and running
        $data = [
            'status' => 'success',
            'message' => 'Project is started and running!',
            'database_init' => $initResult,
        ];

        $jsonData = json_encode($data);
        $response = $response->withHeader('Content-Type', 'application/json');
        $response->getBody()->write($jsonData);
        return $response;
    });




    // Helper functions

    // Handle the database error
    function databaseError(Response $response, $errorMessage)
    {
        $errorResponse = [
            'status' => 'error',
            'message' => 'Database error: ' . $errorMessage,
        ];

        $jsonResponse = json_encode($errorResponse);
        $response = $response->withHeader('Content-Type', 'application/json');
        $response->getBody()->write($jsonResponse);
        return $response;
    }

    // Handle erros in json, error message is taken as input
    function errorJsonReturn(Response $response, $errorMessage)
    {
        $errorResponse = [
            'status' => 'error',
            'message' => $errorMessage,
        ];

        $jsonResponse = json_encode($errorResponse);
        $response = $response->withHeader('Content-Type', 'application/json');
        $response->getBody()->write($jsonResponse);
        return $response;
    }

    // Handle erros in json, error message is taken as input
    function successJsonReturn(Response $response, $responseData)
    {
        $jsonResponse = json_encode($responseData);
        $response = $response->withHeader('Content-Type', 'application/json');
        $response->getBody()->write($jsonResponse);
        return $response;
    }



    $app->get('/userLogin', function (Request $request, Response $response) {
        // Retrieve the user's name from the query parameters 
        $userName = $request->getQueryParams()['name'] ?? '';
        // Check if the username is provided
        if (empty($userName)) {
            return errorJsonReturn($response, 'Username is required.');
        }
        // Check if the username exists in the database
        try {
            $pdo = new \PDO('sqlite:users.db');
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            // Get count of username in users.db, returns 1 if username is taken, else 0
            $checkUsernameQuery = 'SELECT COUNT(*) as count FROM users WHERE name = :username';
            $statement = $pdo->prepare($checkUsernameQuery);
            $statement->bindValue(':username', $userName, \PDO::PARAM_STR);
            $statement->execute();
            $result = $statement->fetch(\PDO::FETCH_ASSOC);
            if ($result['count'] > 0) {
                $_SESSION['user_name'] = $userName;
                $responseData = [
                    'status' => 'success',
                    'message' => 'User login successful',
                    'user_name' => $userName,
                ];
                return successJsonReturn($response, $responseData);
            } else {
                return errorJsonReturn($response, "Username does not exist");
            }
        } catch (\PDOException $e) {
            return databaseError($response, $e->getMessage());
        }
    });




    $app->post('/signUp', function (Request $request, Response $response) {
        // Retrieve the user's name from the query parameters 
        $userName = $request->getParsedBody()['name'] ?? '';
        // Check if the username is provided
        if (empty($userName)) {
            return errorJsonReturn($response, 'Username is required.');
        }
        // Check if the username exists in the database
        try {
            $pdo = new \PDO('sqlite:users.db');
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $checkUsernameQuery = 'SELECT COUNT(*) as count FROM users WHERE name = :username';
            $statement = $pdo->prepare($checkUsernameQuery);
            $statement->bindValue(':username', $userName, \PDO::PARAM_STR);
            $statement->execute();
            $result = $statement->fetch(\PDO::FETCH_ASSOC);

            // if username already exists, then display error message
            if ($result["count"] > 0) {
                errorJsonReturn($response, "Username already exist");
            }
            // else insert the user into the db
            else {
                try {
                    $pdo = new \PDO('sqlite:users.db');
                    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                    $statement = $pdo->prepare('INSERT INTO users (name) VALUES (:name)');
                    $statement->bindValue(':name', $userName, \PDO::PARAM_STR);
                    $success = $statement->execute();
                    if ($success) {
                        $_SESSION['user_name'] = $userName;
                        $responseData = [
                            'status' => 'success',
                            'message' => 'User sign-up successful',
                            'user_name' => $userName,
                        ];
                        return successJsonReturn($response, $responseData);
                    } else {
                        return errorJsonReturn($response, "User could not sign in.");
                    }
                } catch (\PDOException $e) {
                    return databaseError($response, $e->getMessage());
                }
            }
        } catch (\PDOException $e) {
            return databaseError($response, $e->getMessage());
        }
        return errorJsonReturn($response, "Username already exist");
    });





    $app->post('/createChatGroup', function (Request $request, Response $response) {
        // Get the group name from the query parameters to create the table using the group name
        $groupName = $request->getParsedBody()['groupName'] ?? '';
        // Check if the group name is provided
        if (empty($groupName)) {
            $errorMessage = [
                'status' => 'error',
                'message' => 'Error: Group name not provided.'
            ];
            return errorJsonReturn($response, $errorMessage);
        }
        try {
            $dbFilePath = 'sqlite:' . $groupName . '.db';
            $pdo = new \PDO($dbFilePath);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $createTableQuery = 'CREATE TABLE IF NOT EXISTS ' . $groupName . ' (
                        senderUsername TEXT,
                        message_id INTEGER PRIMARY KEY,
                        message TEXT
                    );';

            $pdo->exec($createTableQuery);
            $responseData = [
                'status' => 'success',
                'message' => 'Table "' . $groupName . '" initialized successfully in ' . $dbFilePath . ' with new fields.'
            ];
            // store the group name, user enters the chat after creation of the group, by storing in session, it can be used later
            // to insert into the group names database when sending message
            $_SESSION['group_name'] = $groupName;
            return successJsonReturn($response, $responseData);
        } catch (\PDOException $e) {
            return databaseError($response, $e->getMessage());
        }
    });

    $app->group('/users', function (Group $group) {
        $group->get('', ListUsersAction::class);
        $group->get('/{id}', ViewUserAction::class);
    });

    $app->get('/getChatGroupMessages', function (Request $request, Response $response) {
        $groupName = $request->getQueryParams()['groupName'] ?? '';
        // when the user enters the group chat, store the group name to use it later when 
        // inserting the messages into the correct chat db
        $_SESSION['group_name'] = $groupName;
        $databaseFile = "{$groupName}.db";
        if (!file_exists($databaseFile)) {
            $errorMessage = ['error' => 'Database not found'];
            errorJsonReturn($response, $errorMessage);
        }
        try {
            $pdo = new \PDO("sqlite:{$databaseFile}");
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $query = "SELECT * FROM {$groupName};";
            $statement = $pdo->query($query);
            $records = $statement->fetchAll(\PDO::FETCH_ASSOC);
            $jsonData = json_encode($records);

            $response->getBody()->write($jsonData);
            $response = $response
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Content-Length', strlen($jsonData));
            return $response;
        } catch (\PDOException $e) {
            return databaseError($response, $e->getMessage());
        }
    });







    $app->post('/sendMessage', function (Request $request, Response $response) {
        // Get the message from the query parameters
        $message = $request->getParsedBody()['message'] ?? '';
        //  get group name and username from session which has already been stored
        $groupName = $_SESSION['group_name'] ?? '';
        $sender = $_SESSION['user_name'] ?? '';

        // Check if message is provided
        if (empty($message)) {
            $errorMessage = 'Error: Message not provided.';
            return errorJsonReturn($response, $errorMessage);
        }
        try {
            $dbFilePath = 'sqlite:' . $groupName . '.db';
            $pdo = new \PDO($dbFilePath);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $createTableQuery = "INSERT INTO $groupName (senderUsername, message) VALUES ('$sender', '$message');";
            $pdo->exec($createTableQuery);
            $responseData = [
                'status' => 'success',
                'message' => 'Table "' . $groupName . '" initialized successfully in ' . $dbFilePath . ' with new fields.'
            ];
            return successJsonReturn($response, $responseData);
        } catch (\PDOException $e) {
            return databaseError($response, $e->getMessage());
        }
    });



};