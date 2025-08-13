<?php
require_once 'config.php';
require_once 'auth.php';

if (!Auth::estConnecte()) {
    header('Location: login.php');
    exit;
}

$user = Auth::getUser();
$role_id = $user['role_id'];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('ID de document invalide.');
}

$documentId = (int)$_GET['id'];

// Récupérer le document
$stmt = $pdo->prepare("SELECT * FROM documents WHERE id = ?");
$stmt->execute([$documentId]);
$document = $stmt->fetch();

if (!$document) {
    die('Document non trouvé.');
}

// Vérifier les permissions
$peutTelecharger = false;
if (in_array($role_id, [1, 2])) {
    $peutTelecharger = true;
} elseif (($role_id == 3 || $role_id == 4) && $document['utilisateur_id'] == $user['id']) {
    $peutTelecharger = true;
}

if (!$peutTelecharger) {
    die('Accès refusé.');
}

$filePath = $document['chemin'];

if (!file_exists($filePath)) {
    die('Fichier introuvable.');
}

$filename = basename($filePath);
$fileExtension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

// Définir le type MIME selon l'extension
$mimeTypes = [
    'pdf' => 'application/pdf',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'txt' => 'text/plain',
];

$contentType = $mimeTypes[$fileExtension] ?? 'application/octet-stream';

header('Content-Description: File Transfer');
header('Content-Type: ' . $contentType);
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
exit;
?>
