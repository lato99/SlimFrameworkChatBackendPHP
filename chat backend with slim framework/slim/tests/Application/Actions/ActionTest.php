<?php

declare(strict_types=1);

namespace Tests\Application\Actions;

use App\Application\Actions\Action;
use App\Application\Actions\ActionPayload;
use DateTimeImmutable;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

class ActionTest extends TestCase
{
    public function testActionSetsHttpCodeInRespond()
    {
        $app = $this->getAppInstance();
        $container = $app->getContainer();
        $logger = $container->get(LoggerInterface::class);

        $testAction = new class ($logger) extends Action {
            public function __construct(
                LoggerInterface $loggerInterface
            ) {
                parent::__construct($loggerInterface);
            }

            public function action(): Response
            {
                return $this->respond(
                    new ActionPayload(
                        202,
                        [
                            'willBeDoneAt' => (new DateTimeImmutable())->format(DateTimeImmutable::ATOM)
                        ]
                    )
                );
            }
        };

        $app->get('/test-action-response-code', $testAction);
        $request = $this->createRequest('GET', '/test-action-response-code');
        $response = $app->handle($request);

        $this->assertEquals(202, $response->getStatusCode());
    }

    public function testActionSetsHttpCodeRespondData()
    {
        $app = $this->getAppInstance();
        $container = $app->getContainer();
        $logger = $container->get(LoggerInterface::class);

        $testAction = new class ($logger) extends Action {
            public function __construct(
                LoggerInterface $loggerInterface
            ) {
                parent::__construct($loggerInterface);
            }

            public function action(): Response
            {
                return $this->respondWithData(
                    [
                        'willBeDoneAt' => (new DateTimeImmutable())->format(DateTimeImmutable::ATOM)
                    ],
                    202
                );
            }
        };

        $app->get('/test-action-response-code', $testAction);
        $request = $this->createRequest('GET', '/test-action-response-code');
        $response = $app->handle($request);

        $this->assertEquals(202, $response->getStatusCode());
    }
}




$app->post('/createChatGroup', function (Request $request, Response $response) {
    // Retrieve the user's name from the query parameters (assuming the parameter name is 'name')
    $groupName = $request->getQueryParams()['groupName'] ?? '';

    // Check if the username is provided
    if (empty($groupName)) {
        return invalidRequest($response, 'Group name is required.');
    }

    // Check if the username exists in the database
    try {
    // Connect to the SQLite database (create it if it doesn't exist)
    $pdo = new \PDO('sqlite:allGroups.db');
    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

    // Prepare a query to check if the username already exists
    $checkGroups = 'SELECT COUNT(*) as count FROM :groupName';
    $statement = $pdo->prepare($checkGroups);
    $statement->bindValue(':groupName', $groupName, \PDO::PARAM_STR);
    $statement->execute();

    $result = $statement->fetch(\PDO::FETCH_ASSOC);

    if ($result['count'] == 0) {
    $responseData = [
    'status' => 'success',
    'message' => 'Group name created successfully',
    'user_name' => $groupName,
    ];

    // Encode the data as JSON
    $jsonResponse = json_encode($responseData);

    // Set the Content-Type header to indicate that the response is JSON
    $response = $response->withHeader('Content-Type', 'application/json');

    // Set the JSON content as the response body
    $response->getBody()->write($jsonResponse);

    return $response;
    } else {
    errorJsonReturn($response, "Group already exists.");
    }
    } catch (\PDOException $e) {
    return databaseError($response, $e->getMessage());
    }
    });