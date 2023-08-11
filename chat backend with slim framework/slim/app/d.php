$app->post('/createChatGroup', function (Request $request, Response $response) {
    $data = $request->getParsedBody();
    if (!isset($data['groupName']) || empty($data['groupName'])) {
        $response = $response->withStatus(400);
        $errorData = ['error' => 'Group name is required'];
        $jsonData = json_encode($errorData);
        $response = $response
        ->withHeader('Content-Type', 'application/json')
        ->withHeader('Content-Length', strlen($jsonData));
        $response->getBody()->write($jsonData);
        return $response;
    }
    $groupName = $data['groupName'];
    try {
        $pdo = new \PDO('sqlite:allGroups.db');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $createTableQuery = 'CREATE TABLE IF NOT EXISTS chat_groups (
                                id INTEGER PRIMARY KEY AUTOINCREMENT,
                                name TEXT NOT NULL
                            );';
        $pdo->exec($createTableQuery);
        $statement = $pdo->prepare('INSERT INTO chat_groups (name) VALUES (:name)');
        $statement->bindValue(':name', $groupName, \PDO::PARAM_STR);
        $success = $statement->execute();
        if (!$success) {
            $response = $response->withStatus(500);
            $errorData = ['error' => 'Failed to create the chat group'];
            $jsonData = json_encode($errorData);
            $response = $response
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Content-Length', strlen($jsonData));
            $response->getBody()->write($jsonData);
            return $response;
        }
        $lastInsertId = $pdo->lastInsertId();
        $selectStatement = $pdo->prepare('SELECT * FROM chat_groups WHERE id = :id');
        $selectStatement->bindValue(':id', $lastInsertId, \PDO::PARAM_INT);
        $selectStatement->execute();
        $group = $selectStatement->fetch(\PDO::FETCH_ASSOC);
            $groupData = ['success' => true, 'group' => $group];
            $jsonData = json_encode($groupData);
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write($jsonData);
            $response = $response
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Content-Length', strlen($jsonData))
                ->withStatus(201);
            return $response;
    } catch (\PDOException $e) {
        $errorData = ['error' => 'Database error: ' . $e->getMessage()];
        $jsonData = json_encode($errorData);
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write($jsonData);
        $response = $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Content-Length', strlen($jsonData))
            ->withStatus(500);
        return $response;
    }
});