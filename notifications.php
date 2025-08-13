<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'utilisateurs.php';

// Vérifier si l'utilisateur est connecté
if (!Auth::estConnecte()) {
    header('Location: login.php');
    exit;
}

$utilisateurs = new Utilisateurs($pdo);
$user_id = $_SESSION['user']['id'];
$user = $utilisateurs->getUtilisateurById($user_id);


if (!$user) {
    header('Location: login.php');
    exit;
}

$errors = [];
$success = [];

// Traitement de la soumission du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type_notification = $_POST['type_notification'] ?? '';
    $message = trim($_POST['message'] ?? '');
    
    // Validation
    if (!$type_notification || !$message) {
        $errors[] = "Veuillez remplir tous les champs obligatoires.";
    } else {
        // Enregistrer la notification
        $stmt = $pdo->prepare("INSERT INTO notifications (utilisateur_id, type_notification, message, statut, date_envoi) VALUES (?, ?, ?, 'non_lu', NOW())");
        if ($stmt->execute([$user_id, $type_notification, $message])) {
            $success[] = "Notification envoyée avec succès.";
        } else {
            $errors[] = "Erreur lors de l'envoi de la notification.";
        }
    }
}

if (Auth::estSuperAdmin() || Auth::aRole('professeur')) {
    // Récupérer toutes les notifications pour super admin et professeurs avec nom et prénom de l'utilisateur
    $stmt = $pdo->prepare("SELECT n.*, u.nom, u.prenom FROM notifications n JOIN utilisateurs u ON n.utilisateur_id = u.id ORDER BY n.date_envoi DESC");
    $stmt->execute();
} else {
    // Récupérer uniquement les notifications de l'utilisateur
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE utilisateur_id = ? ORDER BY date_envoi DESC");
    $stmt->execute([$user_id]);
}
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);



?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Notifications - PlagiaTrack</title>
</head>
<body>
    <h1>Notifications</h1>

    <?php if ($errors): ?>
        <div style="color: red; background: #f8d7da; padding: 10px; margin-bottom: 15px;">
            <?php foreach ($errors as $error): ?>
                <div><?=htmlspecialchars($error)?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div style="color: green; background: #d4edda; padding: 10px; margin-bottom: 15px;">
            <?php foreach ($success as $success_msg): ?>
                <div><?=htmlspecialchars($success_msg)?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <h2>Envoyer une notification</h2>
    <form method="post" action="">
        <label for="type_notification">Type de notification:</label>
        <select id="type_notification" name="type_notification" required>
            <option value="">Sélectionnez un type</option>
            <option value="promotion_professeur">Demande de promotion en tant que professeur</option>
            <option value="promotion_admin">Demande de promotion en tant qu'administrateur</option>
            <option value="lettre_libre">Lettre libre</option>
        </select><br><br>

        <label for="message">Message:</label>
        <textarea id="message" name="message" rows="5" cols="50" required></textarea><br><br>

        <button type="submit">Envoyer la notification</button>
    </form>

    <h2>Mes notifications</h2>

    <form method="get" action="">
        <label for="search_nom">Nom d'utilisateur:</label>
        <input type="text" id="search_nom" name="search_nom" value="<?=htmlspecialchars($_GET['search_nom'] ?? '')?>">
        
        <label for="search_type">Type de notification:</label>
        <select id="search_type" name="search_type">
            <option value="">Tous</option>
            <option value="promotion_professeur" <?= (($_GET['search_type'] ?? '') === 'promotion_professeur') ? 'selected' : '' ?>>Demande de promotion en tant que professeur</option>
            <option value="promotion_admin" <?= (($_GET['search_type'] ?? '') === 'promotion_admin') ? 'selected' : '' ?>>Demande de promotion en tant qu'administrateur</option>
            <option value="lettre_libre" <?= (($_GET['search_type'] ?? '') === 'lettre_libre') ? 'selected' : '' ?>>Lettre libre</option>
        </select>

        <label for="search_date">Date d'envoi (YYYY-MM-DD):</label>
        <input type="date" id="search_date" name="search_date" value="<?=htmlspecialchars($_GET['search_date'] ?? '')?>">

        <button type="submit">Rechercher</button>
    </form>

    <?php if ($notifications): ?>
        <table border="1" cellpadding="10" style="margin-top: 15px;">
            <thead>
                <tr>
                    <th>Utilisateur</th>
                    <th>Type</th>
                    <th>Message</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($notifications as $notification): ?>
                    <tr>
                        <td><?=htmlspecialchars($notification['nom'] . ' ' . $notification['prenom'])?></td>
                        <td><?=htmlspecialchars($notification['type_notification'])?></td>
                        <td><?=htmlspecialchars($notification['message'])?></td>
                        <td><?=htmlspecialchars($notification['date_envoi'])?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Aucune notification trouvée.</p>
    <?php endif; ?>
$search_nom = $_GET['search_nom'] ?? '';
$search_type = $_GET['search_type'] ?? '';
$search_date = $_GET['search_date'] ?? '';

if (Auth::estSuperAdmin() || Auth::aRole('professeur')) {
    // Construire la requête avec filtres
    $query = "SELECT n.*, u.nom, u.prenom FROM notifications n JOIN utilisateurs u ON n.utilisateur_id = u.id WHERE 1=1";
    $params = [];

    if ($search_nom !== '') {
        $query .= " AND (u.nom LIKE ? OR u.prenom LIKE ?)";
        $params[] = "%$search_nom%";
        $params[] = "%$search_nom%";
    }
    if ($search_type !== '') {
        $query .= " AND n.type_notification = ?";
        $params[] = $search_type;
    }
    if ($search_date !== '') {
        $query .= " AND DATE(n.date_envoi) = ?";
        $params[] = $search_date;
    }
    $query .= " ORDER BY n.date_envoi DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
} else {
    // Récupérer uniquement les notifications de l'utilisateur
    $stmt = $pdo->prepare("SELECT n.*, u.nom, u.prenom FROM notifications n JOIN utilisateurs u ON n.utilisateur_id = u.id WHERE utilisateur_id = ? ORDER BY n.date_envoi DESC");
    $stmt->execute([$user_id]);
}
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
