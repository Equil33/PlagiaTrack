<?php
require_once 'config.php';
require_once 'utilisateurs.php';
require_once 'auth.php';

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

// Traitement de la mise à jour du profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');

    // Validation
    if (!$nom || !$prenom || !$email) {
        $errors[] = "Veuillez remplir tous les champs obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'email n'est pas valide.";
    } else {
        // Vérifier si l'email est déjà utilisé par un autre utilisateur
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Cet email est déjà utilisé par un autre utilisateur.";
        } else {
            // Mettre à jour le profil
            $stmt = $pdo->prepare("UPDATE utilisateurs SET nom = ?, prenom = ?, email = ? WHERE id = ?");
            if ($stmt->execute([$nom, $prenom, $email, $user_id])) {
                $success[] = "Profil mis à jour avec succès.";
                // Rafraîchir les données de l'utilisateur
                $user = $utilisateurs->getUtilisateurById($user_id);
            } else {
                $errors[] = "Erreur lors de la mise à jour du profil.";
            }
        }
    }
}

// Traitement du changement de mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!$current_password || !$new_password || !$confirm_password) {
        $errors[] = "Veuillez remplir tous les champs du mot de passe.";
    } elseif ($new_password !== $confirm_password) {
        $errors[] = "Les nouveaux mots de passe ne correspondent pas.";
    } elseif (strlen($new_password) < 6) {
        $errors[] = "Le nouveau mot de passe doit contenir au moins 6 caractères.";
    } else {
        // Vérifier le mot de passe actuel
        if (!password_verify($current_password, $user['mot_de_passe_hash'])) {
            $errors[] = "Le mot de passe actuel est incorrect.";
        } else {
            // Mettre à jour le mot de passe
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe_hash = ? WHERE id = ?");
            if ($stmt->execute([$new_password_hash, $user_id])) {
                $success[] = "Mot de passe modifié avec succès.";
            } else {
                $errors[] = "Erreur lors de la modification du mot de passe.";
            }
        }
    }
}

// Récupérer le rôle de l'utilisateur
$stmt = $pdo->prepare("SELECT nom FROM roles WHERE id = ?");
$stmt->execute([$user['role_id']]);
$role = $stmt->fetch();
$user_role = $role ? $role['nom'] : 'Inconnu';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mon Profil - PlagiaTrack</title>
</head>
<body>
    <h1>Mon Profil</h1>

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

    <h2>Informations personnelles</h2>
    <form method="post" action="">
        <input type="hidden" name="update_profile" value="1">
        
        <label for="nom">Nom:</label>
        <input type="text" id="nom" name="nom" value="<?=htmlspecialchars($user['nom'])?>" required><br><br>
        
        <label for="prenom">Prénom:</label>
        <input type="text" id="prenom" name="prenom" value="<?=htmlspecialchars($user['prenom'])?>" required><br><br>
        
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?=htmlspecialchars($user['email'])?>" required><br><br>
        
        <label>Rôle:</label>
        <input type="text" value="<?=htmlspecialchars($user_role)?>" readonly><br><br>
        
        <button type="submit">Mettre à jour le profil</button>
    </form>

    <h2>Changer le mot de passe</h2>
    <form method="post" action="">
        <input type="hidden" name="change_password" value="1">
        
        <label for="current_password">Mot de passe actuel:</label>
        <input type="password" id="current_password" name="current_password" required><br><br>
        
        <label for="new_password">Nouveau mot de passe:</label>
        <input type="password" id="new_password" name="new_password" required><br><br>
        
        <label for="confirm_password">Confirmer le nouveau mot de passe:</label>
        <input type="password" id="confirm_password" name="confirm_password" required><br><br>
        
        <button type="submit">Changer le mot de passe</button>
    </form>

    <h2>Informations du compte</h2>
    <p><strong>ID Utilisateur:</strong> <?=htmlspecialchars($user['id'])?></p>
    <p><strong>Statut:</strong> <?=htmlspecialchars($user['statut'])?></p>
    <p><strong>Date de création:</strong> <?=htmlspecialchars($user['date_creation'])?></p>
    <p><strong>Dernière connexion:</strong> <?=htmlspecialchars($user['derniere_connexion'] ?? 'Jamais')?></p>

    <p><a href="dashboard.php">Retour au tableau de bord</a></p>
</body>
</html>
