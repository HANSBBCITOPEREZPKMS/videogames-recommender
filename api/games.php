<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");
header('Content-Type: application/json');
echo json_encode(['csv_path' => __DIR__ . '/../data/games.csv']);
exit;

$archivo = fopen(__DIR__ . '/../data/games.csv', 'r');



// Función para leer el CSV
function leerCSV($archivo) {
    $juegos = [];
    if (($handle = fopen($archivo, "r")) !== FALSE) {
        $primera = true;
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if ($primera) { $primera = false; continue; }
            $juegos[] = ["nombre" => $data[0], "caracteristica" => $data[1]];
        }
        fclose($handle);
    }
    return $juegos;
}

// Función para escribir al CSV
function escribirCSV($archivo, $juegos) {
    $fp = fopen($archivo, 'w');
    fputcsv($fp, ["nombre", "caracteristica"]);
    foreach ($juegos as $juego) {
        fputcsv($fp, [$juego["nombre"], $juego["caracteristica"]]);
    }
    fclose($fp);
}

$method = $_SERVER['REQUEST_METHOD'];

// GET → buscar por característica
if ($method === 'GET') {
    if (!isset($_GET['caracteristica'])) {
        echo json_encode(["error" => "Falta el parámetro 'caracteristica'"]);
        exit;
    }

    $caracteristica = strtolower(trim($_GET['caracteristica']));
    $juegos = leerCSV($archivo);
    $resultado = [];

    foreach ($juegos as $j) {
        if (strpos(strtolower($j['caracteristica']), $caracteristica) !== false) {
            $resultado[] = $j['nombre'];
        }
    }

    if ($resultado) {
        echo json_encode(["juegos" => $resultado]);
    } else {
        echo json_encode(["mensaje" => "No se encontraron juegos con esa característica"]);
    }
}

// POST → agregar un nuevo juego
elseif ($method === 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);
    if (!$input || !isset($input['nombre']) || !isset($input['caracteristica'])) {
        echo json_encode(["error" => "Faltan campos: nombre y caracteristica"]);
        exit;
    }

    $juegos = leerCSV($archivo);
    $juegos[] = ["nombre" => $input['nombre'], "caracteristica" => $input['caracteristica']];
    escribirCSV($archivo, $juegos);

    echo json_encode(["mensaje" => "Juego agregado correctamente"]);
}

// PUT → actualizar juego por nombre
elseif ($method === 'PUT') {
    $input = json_decode(file_get_contents("php://input"), true);
    if (!$input || !isset($input['nombre']) || !isset($input['caracteristica'])) {
        echo json_encode(["error" => "Faltan campos: nombre y caracteristica"]);
        exit;
    }

    $juegos = leerCSV($archivo);
    $actualizado = false;

    foreach ($juegos as &$juego) {
        if (strtolower($juego['nombre']) === strtolower($input['nombre'])) {
            $juego['caracteristica'] = $input['caracteristica'];
            $actualizado = true;
            break;
        }
    }

    if ($actualizado) {
        escribirCSV($archivo, $juegos);
        echo json_encode(["mensaje" => "Juego actualizado correctamente"]);
    } else {
        echo json_encode(["error" => "No se encontró el juego especificado"]);
    }
}

// DELETE → eliminar por nombre
elseif ($method === 'DELETE') {
    $input = json_decode(file_get_contents("php://input"), true);
    if (!$input || !isset($input['nombre'])) {
        echo json_encode(["error" => "Falta el campo 'nombre'"]);
        exit;
    }

    $juegos = leerCSV($archivo);
    $nuevo = [];
    $eliminado = false;

    foreach ($juegos as $j) {
        if (strtolower($j['nombre']) !== strtolower($input['nombre'])) {
            $nuevo[] = $j;
        } else {
            $eliminado = true;
        }
    }

    if ($eliminado) {
        escribirCSV($archivo, $nuevo);
        echo json_encode(["mensaje" => "Juego eliminado correctamente"]);
    } else {
        echo json_encode(["error" => "No se encontró el juego a eliminar"]);
    }
}

// OPTIONS → respuesta para preflight CORS
elseif ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

else {
    echo json_encode(["error" => "Método no permitido"]);
}
?>
