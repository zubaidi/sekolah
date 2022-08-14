<?php
/*
**
*/

$RESPON = [
    'status' => 'error',
    'data' => null
];

if (isset($_SERVER['HTTP_CONTENT_TYPE']) && $_SERVER['HTTP_CONTENT_TYPE'] !== 'application/json'){
    http_response_code(404);
    exit();
}

$db = new PDO('mysql:host=localhost;dbname=sekolah','root');

$queryString = [];
$rawBody = file_get_contents('php://input', 'r');
$body = json_decode($rawBody, true);

function getNIS(){
    if(!strpos($_SERVER['REQUEST_URI'], '?')) return false;
    $path = explode('?', $_SERVER['REQUEST_URI'])[1];
    $segment = explode('/', $path);
    return +$segment[1];
}

switch (strtolower($_SERVER['REQUEST_METHOD'])){
    case 'post':
        // eksekusi data
        try{
            if (!isset($body['nis'], $body['nama_siswa'], $body['id_jurusan'], $body['id_walikelas'])){
                throw new InvalidArgumentException('Invalid Form');
            }

            $stmt = $db -> prepare('INSERT INTO siswa(NIS, NAMA_SISWA, ID_JURUSAN, KD_WALI) 
                                    VALUES (:nis, :nama, :id_j, :id_w)');
            $stmt -> execute([
                ':nis' => $body['nis'],
                ':nama' => $body['nama_siswa'],
                ':id_j' => $body['id_jurusan'],
                ':id_w' => $body['id_walikelas'],
            ]);
            http_response_code(201);
            $RESPON['status'] = 'success';
            $RESPON['data'] = [];
        } catch (Throwable $error) {
            if ($error instanceof InvalidArgumentException){
                http_response_code(400);
            }else{
                http_response_code(500);
            }

            $RESPON['error'] = $error->getMessage();

        }
        break;
    case 'get':
        $nis = getNIS();
        if($nis){
            $stmt = $db->prepare('SELECT * FROM siswa WHERE NIS = :nis');
            $stmt -> execute([':nis' => $nis]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
        }else{
            $stmt = $db->query('SELECT s.NIS, s.NAMA_SISWA, j.NAMA_JURUSAN AS NAMA_JURUSAN, w.NAMA_WALI_KELAS AS NAMA_WALI 
                    FROM siswa s
                    JOIN jurusan j on j.ID_JURUSAN = s.ID_JURUSAN 
                    JOIN walikelas w on w.KD_WALI = s.KD_WALI');
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        if(!empty($data)){
            $RESPON['status'] = 'success';
            // $RESPON['data'] = $data;
            $RESPON['data'] = $data;

                // foreach ($data as $i => $row){
                //     $RESPON['data'][$i] = [
                //         'nis' => $row['NIS'],
                //         'nama_siswa' => $row['NAMA_SISWA'],
                //         'nama_jurusan' => $row['NAMA_JURUSAN'],
                //         'nama_walikelas' => $row['NAMA_WALI'],
                //     ];
                // }
        }else{
            http_response_code(404);
        }
        break;
    case 'delete':
        $nis = getNIS();
        if(!$nis){
            http_response_code(404);
            break;
        }
        $stmt = $db->prepare('SELECT * FROM siswa WHERE NIS = :nis');
        $stmt->bindParam(':nis', $nis, PDO::PARAM_INT);
        $stmt -> execute();

        if(!$stmt -> rowCount()){
            http_response_code(404);
            break;
        }

        $stmt = $db->prepare('DELETE FROM siswa WHERE NIS = :nis');
        $stmt->execute([':nis' => $nis]);
        http_response_code(204);
        break;
    case 'put':
        $nis = getNIS();
        if(!$nis){}
        
        if (!isset($body['nama_siswa'], $body['id_jurusan'], $body['id_walikelas'])){
            throw new InvalidArgumentException('Invalid Form');
        }
        
        $stmt = $db->prepare('SELECT * FROM siswa WHERE NIS = :nis');
        $stmt->bindParam(':nis', $nis, PDO::PARAM_INT);

        if($stmt->rowCount()){
            http_response_code(404);
            break;
        }
        // eksekusi database update
        $stmt = $db->prepare('UPDATE siswa SET NAMA_SISWA=:nama, ID_JURUSAN=:id_j, KD_WALI=:id_w 
                                WHERE NIS = :nis');
        $stmt -> execute([
            ':nis' => $nis,
            ':nama' => $body['nama_siswa'],
            ':id_j' => $body['id_jurusan'],
            ':id_w' => $body['id_walikelas'],
        ]);
        http_response_code(201);
        $RESPON['status'] = 'success';
        $RESPON['data'] = 'berhasil diubah';
        break;
    default:
        http_response_code(503);
        break;
}

header('Content-Type: application/json');
echo json_encode($RESPON);