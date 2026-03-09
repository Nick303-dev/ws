<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

 
$host     = "db";
$db_name  = "5cinf";
$username = "root";
$password = "admin";  


try {
$conn = new PDO("mysql:host=$host;port=3306;dbname=$db_name;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $exception) {
    http_response_code(500);
    echo json_encode(["message" => "Errore di connessione al database: " . $exception->getMessage()]);
    exit();
}


$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
 
    case 'GET':
        if (isset($_GET['id'])) {
            
            $id = intval($_GET['id']);
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
        $num = $stmt->rowCount();

        if ($num > 0) {
            $users_arr = $stmt->fetchAll(PDO::FETCH_ASSOC);
            http_response_code(200);
            echo json_encode($users_arr);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Nessun utente trovato."]);
        }
        break;

 
    case 'POST':
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->nome) && !empty($data->email)) {

           
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


            $query = "INSERT INTO users (nome, email) VALUES (:nome, :email)";
            $stmt  = $conn->prepare($query);
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

        } else {
            http_response_code(400);
            echo json_encode(["message" => "Dati incompleti. Invia 'nome' ed 'email'."]);
        }
        break;


    default:
        http_response_code(405);
        echo json_encode(["message" => "Metodo non consentito. Usa GET o POST."]);
        break;
}
?>