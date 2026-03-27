<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

$host     = "db";
$db_name  = "5cinf";
$username = "root";
$password = "admin";

try {
    $conn = new PDO("mysql:host=$host;port=3306;dbname=$db_name;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
    http_response_code(500);
    echo json_encode(["message" => "Errore di connessione: " . $exception->getMessage()]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    case 'GET':
        if (isset($_GET['id'])) {
            $id    = intval($_GET['id']);
            $query = "SELECT id, nome, email FROM users WHERE id = :id";
            $stmt  = $conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        } elseif (isset($_GET['nome'])) {
            $nome  = "%" . $_GET['nome'] . "%";
            $query = "SELECT id, nome, email FROM users WHERE nome LIKE :nome";
            $stmt  = $conn->prepare($query);
            $stmt->bindParam(':nome', $nome);

        } else {
            $query = "SELECT id, nome, email FROM users ORDER BY id ASC";
            $stmt  = $conn->prepare($query);
        }

        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Nessun utente trovato."]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));

        if (empty($data->nome) || empty($data->email)) {
            http_response_code(400);
            echo json_encode(["message" => "Dati incompleti. Invia 'nome' ed 'email'."]);
            break;
        }

        $nome  = htmlspecialchars(strip_tags($data->nome));
        $email = htmlspecialchars(strip_tags($data->email));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(["message" => "Formato email non valido."]);
            break;
        }

        $check = $conn->prepare("SELECT id FROM users WHERE email = :email");
        $check->bindParam(':email', $email);
        $check->execute();
        if ($check->rowCount() > 0) {
            http_response_code(409);
            echo json_encode(["message" => "Email già registrata."]);
            break;
        }

        $stmt = $conn->prepare("INSERT INTO users (nome, email) VALUES (:nome, :email)");
        $stmt->bindParam(':nome',  $nome);
        $stmt->bindParam(':email', $email);

        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode([
                "message" => "Utente creato con successo.",
                "id"      => $conn->lastInsertId()
            ]);
        } else {
            http_response_code(503);
            echo json_encode(["message" => "Impossibile creare l'utente."]);
        }
        break;

   case 'PUT':
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(["message" => "ID obbligatorio per l'aggiornamento."]);
            break;
        }

        $id   = intval($_GET['id']);
        $data = json_decode(file_get_contents("php://input"));

        if (empty($data->nome) && empty($data->email)) {
            http_response_code(400);
            echo json_encode(["message" => "Invia almeno 'nome' o 'email' da aggiornare."]);
            break;
        }

        $check = $conn->prepare("SELECT id FROM users WHERE id = :id");
        $check->bindParam(':id', $id, PDO::PARAM_INT);
        $check->execute();
        if ($check->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(["message" => "Utente non trovato."]);
            break;
        }

        if (!empty($data->nome) && !empty($data->email)) {
            $email = htmlspecialchars(strip_tags($data->email));

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode(["message" => "Formato email non valido."]);
                break;
            }

            $emailCheck = $conn->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
            $emailCheck->execute([':email' => $email, ':id' => $id]);
            if ($emailCheck->rowCount() > 0) {
                http_response_code(409);
                echo json_encode(["message" => "Email già in uso da un altro utente."]);
                break;
            }

            $nome  = htmlspecialchars(strip_tags($data->nome));
            $query = "UPDATE users SET nome = :nome, email = :email WHERE id = :id";
            $stmt  = $conn->prepare($query);
            $stmt->bindParam(':nome',  $nome);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':id',    $id, PDO::PARAM_INT);

        } elseif (!empty($data->nome)) {
            $nome  = htmlspecialchars(strip_tags($data->nome));
            $query = "UPDATE users SET nome = :nome WHERE id = :id";
            $stmt  = $conn->prepare($query);
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':id',   $id, PDO::PARAM_INT);

        } else {
            $email = htmlspecialchars(strip_tags($data->email));

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode(["message" => "Formato email non valido."]);
                break;
            }

            $emailCheck = $conn->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
            $emailCheck->execute([':email' => $email, ':id' => $id]);
            if ($emailCheck->rowCount() > 0) {
                http_response_code(409);
                echo json_encode(["message" => "Email già in uso da un altro utente."]);
                break;
            }

            $query = "UPDATE users SET email = :email WHERE id = :id";
            $stmt  = $conn->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':id',    $id, PDO::PARAM_INT);
        }

        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(["message" => "Utente aggiornato con successo."]);
        } else {
            http_response_code(503);
            echo json_encode(["message" => "Impossibile aggiornare l'utente."]);
        }
        break;

    case 'DELETE':
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(["message" => "ID obbligatorio per la cancellazione."]);
            break;
        }

        $id = intval($_GET['id']);

        $check = $conn->prepare("SELECT id FROM users WHERE id = :id");
        $check->bindParam(':id', $id, PDO::PARAM_INT);
        $check->execute();
        if ($check->rowCount() === 0) {
            http_response_code(4nmk,04);
            echo json_encode(["message" => "Utente non trovato."]);
            break;
        }

        $stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(["message" => "Utente eliminato con successo."]);
        } else {
            http_response_code(503);
            echo json_encode(["message" => "Impossibile eliminare l'utente."]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["message" => "Metodo non consentito."]);
        break;
}
?>
