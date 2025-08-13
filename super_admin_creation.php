<?php
require_once 'config.php';
require_once 'utilisateurs.php';

$utilisateurs = new Utilisateurs($pdo);

if ($utilisateurs->superAdminExiste()) {
    header('Location: login.php');
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $motDePasse = $_POST['mot_de_passe'] ?? '';
    $motDePasseConfirm = $_POST['mot_de_passe_confirm'] ?? '';

    if (!$nom || !$prenom || !$email || !$motDePasse || !$motDePasseConfirm) {
        $errors[] = "Tous les champs sont obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'adresse email n'est pas valide.";
    } elseif ($motDePasse !== $motDePasseConfirm) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    } else {
        // Récupérer l'id du rôle super_admin
        $stmt = $pdo->prepare("SELECT id FROM roles WHERE nom = 'super_admin'");
        $stmt->execute();
        $role = $stmt->fetch();
        if (!$role) {
            $errors[] = "Le rôle super_admin n'existe pas dans la base de données.";
        } else {
            $role_id = $role['id'];
            $utilisateurs->creerUtilisateur($nom, $prenom, $email, $motDePasse, $role_id, true);
            header('Location: login.php');
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Création du Super Administrateur</title>
    <link href="../templatemo_455_visual_admin/css/bootstrap.min.css" rel="stylesheet" />
    <link href="../templatemo_455_visual_admin/css/font-awesome.min.css" rel="stylesheet" />
    <link href="../templatemo_455_visual_admin/css/templatemo-style.css" rel="stylesheet" />
</head>
<body>
  <div class="templatemo-flex-row">
    <div class="templatemo-sidebar">
      <header class="templatemo-site-header">
        <div class="square"></div>
        <h1>PlagiaTrack</h1>
      </header>
      <div class="profile-photo-container">
        <img src="../templatemo_455_visual_admin/images/profile-photo.jpg" alt="Profile Photo" class="img-responsive" />
        <div class="profile-photo-overlay"></div>
      </div>
      <nav class="templatemo-left-nav">
        <ul>
          <li><a href="../backend/dashboard.php"><i class="fa fa-home fa-fw"></i>TABLEAU DE BORD</a></li>
          <li><a href="../backend/gestion_utilisateurs.php"><i class="fa fa-users fa-fw"></i>GESTION DES UTILISATEURS</a></li>
          <li><a href="../backend/gestion_documents.php"><i class="fa fa-file fa-fw"></i>GESTION DES DOCUMENTS</a></li>
          <li>
            <a href="../backend/analyse_plagiat.php">
              <img src="../data_analyse.svg" alt="Analyse Icon" style="width:16px; height:16px; vertical-align:middle; margin-right:5px;" />
              ANALYSE DE PLAGIAT
            </a>
          </li>
          <li><a href="../backend/rapports.php"><i class="fa fa-file-pdf-o fa-fw"></i>RAPPORTS DE PLAGIAT</a></li>
          <!-- Suppression du lien vers la page Administration -->
          <!-- <li><a href="../backend/administration.php"><i class="fa fa-cog fa-fw"></i>ADMINISTRATION</a></li> -->
          <li><a href="../backend/logout.php"><i class="fa fa-sign-out fa-fw"></i>DÉCONNEXION</a></li>
        </ul>
      </nav>
    </div>
    <div class="templatemo-content col-1 light-gray-bg">
      <div class="templatemo-content-container">
        <h1>Création du Super Administrateur</h1>
        <?php if ($errors): ?>
            <ul style="color:red;">
                <?php foreach ($errors as $error): ?>
                    <li><?=htmlspecialchars($error)?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <form method="post" action="">
            <label>Nom : <input type="text" name="nom" required></label><br />
            <label>Prénom : <input type="text" name="prenom" required></label><br />
            <label>Email : <input type="email" name="email" required></label><br />
            <label>Mot de passe : <input type="password" name="mot_de_passe" required></label><br />
            <label>Confirmer mot de passe : <input type="password" name="mot_de_passe_confirm" required></label><br />
            <button type="submit">Créer le Super Administrateur</button>
        </form>
      </div>
    </div>
  </div>
  <script src="../templatemo_455_visual_admin/js/jquery-1.11.2.min.js"></script>
  <script src="../templatemo_455_visual_admin/js/jquery-migrate-1.2.1.min.js"></script>
  <script src="../templatemo_455_visual_admin/js/bootstrap-filestyle.min.js"></script>
  <script src="../templatemo_455_visual_admin/js/templatemo-script.js"></script>
</body>
</html>
