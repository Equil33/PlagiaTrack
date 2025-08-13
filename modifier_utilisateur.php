<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'utilisateurs.php';

if (!Auth::estConnecte()) {
    header('Location: login.php');
    exit;
}

$user = Auth::getUser();
$utilisateurs = new Utilisateurs($pdo);

// Vérifier que l'utilisateur est administrateur ou super admin
if (!in_array($user['role_id'], [1, 2])) {
    echo "Accès refusé.";
    exit;
}

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header('Location: gestion_utilisateurs.php');
    exit;
}

$utilisateur = $utilisateurs->getUtilisateurById($id);
if (!$utilisateur) {
    echo "Utilisateur non trouvé.";
    exit;
}

$stmt = $pdo->query("SELECT * FROM roles ORDER BY id ASC");
$liste_roles = $stmt->fetchAll();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role_id = intval($_POST['role_id'] ?? 0);
    $statut = $_POST['statut'] ?? 'actif';

    if ($nom && $prenom && $email && $role_id && $statut) {
        $utilisateurs->modifierUtilisateur($id, $nom, $prenom, $email, $role_id, $statut);
        header('Location: gestion_utilisateurs.php');
        exit;
    } else {
        $errors[] = "Tous les champs sont obligatoires.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Modifier utilisateur - PlagiaTrack</title>
  <link href="../templatemo_455_visual_admin/css/bootstrap.min.css" rel="stylesheet">
  <link href="../templatemo_455_visual_admin/css/font-awesome.min.css" rel="stylesheet">
  <link href="../templatemo_455_visual_admin/css/templatemo-style.css" rel="stylesheet">
  <style>
    .form-container {
      max-width: 600px;
      margin: 50px auto;
      padding: 30px;
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 0 20px rgba(0,0,0,0.1);
    }
    .form-header {
      text-align: center;
      margin-bottom: 30px;
      color: #333;
    }
    .form-group {
      margin-bottom: 20px;
    }
    .form-group label {
      display: block;
      margin-bottom: 5px;
      font-weight: bold;
      color: #555;
    }
    .form-control {
      width: 100%;
      padding: 12px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 16px;
      transition: border-color 0.3s;
    }
    .form-control:focus {
      border-color: #007bff;
      outline: none;
    }
    .btn-primary {
      background-color: #007bff;
      border: none;
      padding: 12px 30px;
      font-size: 16px;
      border-radius: 4px;
      cursor: pointer;
      transition: background-color 0.3s;
    }
    .btn-primary:hover {
      background-color: #0056b3;
    }
    .btn-secondary {
      background-color: #6c757d;
      border: none;
      padding: 12px 30px;
      font-size: 16px;
      border-radius: 4px;
      cursor: pointer;
      transition: background-color 0.3s;
      color: white;
      text-decoration: none;
      display: inline-block;
      margin-left: 10px;
    }
    .btn-secondary:hover {
      background-color: #545b62;
    }
    .error-message {
      color: #dc3545;
      background-color: #f8d7da;
      border: 1px solid #f5c6cb;
      padding: 10px;
      border-radius: 4px;
      margin-bottom: 20px;
    }
    .form-actions {
      text-align: center;
      margin-top: 30px;
    }
  </style>
</head>
<body>
  <div class="templatemo-flex-row">
    <div class="templatemo-sidebar">
      <header class="templatemo-site-header">
        <div class="square"></div>
        <h1>PlagiaTrack</h1>
      </header>
      <div class="profile-photo-container">
        <img src="../templatemo_455_visual_admin/images/profile-photo.jpg" alt="Profile Photo" class="img-responsive">
        <div class="profile-photo-overlay"></div>
      </div>
      <nav class="templatemo-left-nav">
        <ul>
          <li><a href="../backend/dashboard.php"><i class="fa fa-home fa-fw"></i>Tableau de bord</a></li>
          <li><a href="../backend/gestion_utilisateurs.php"><i class="fa fa-users fa-fw"></i>Gestion des utilisateurs</a></li>
          <li><a href="../backend/gestion_documents.php"><i class="fa fa-file fa-fw"></i>Gestion des documents</a></li>
          <li>
            <a href="../backend/analyse_plagiat.php">
              <img src="../data_analyse.svg" alt="Analyse Icon" style="width:16px; height:16px; vertical-align:middle; margin-right:5px;">
              Analyse de plagiat
            </a>
          </li>
          <li><a href="../backend/rapports.php"><i class="fa fa-file-pdf-o fa-fw"></i>Rapports de plagiat</a></li>
          <li><a href="../backend/logout.php"><i class="fa fa-sign-out fa-fw"></i>Déconnexion</a></li>
        </ul>
      </nav>
    </div>
    
    <div class="templatemo-content col-1 light-gray-bg">
      <div class="templatemo-content-container">
        <div class="form-container">
          <div class="form-header">
            <h1><i class="fa fa-user-edit"></i> Modifier utilisateur</h1>
            <p>Modification de l'utilisateur : <?=htmlspecialchars($utilisateur['prenom'] . ' ' . $utilisateur['nom'])?></p>
          </div>

          <?php if ($errors): ?>
            <div class="error-message">
              <?php foreach ($errors as $error): ?>
                <p><i class="fa fa-exclamation-triangle"></i> <?=htmlspecialchars($error)?></p>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <form method="post" action="">
            <div class="form-group">
              <label for="nom">Nom :</label>
              <input type="text" id="nom" name="nom" class="form-control" 
                     value="<?=htmlspecialchars($utilisateur['nom'])?>" required>
            </div>

            <div class="form-group">
              <label for="prenom">Prénom :</label>
              <input type="text" id="prenom" name="prenom" class="form-control" 
                     value="<?=htmlspecialchars($utilisateur['prenom'])?>" required>
            </div>

            <div class="form-group">
              <label for="email">Email :</label>
              <input type="email" id="email" name="email" class="form-control" 
                     value="<?=htmlspecialchars($utilisateur['email'])?>" required>
            </div>

            <div class="form-group">
              <label for="role_id">Rôle :</label>
              <select id="role_id" name="role_id" class="form-control" required>
                <?php foreach ($liste_roles as $role): ?>
                  <option value="<?=htmlspecialchars($role['id'])?>" 
                          <?=($role['id'] == $utilisateur['role_id']) ? 'selected' : ''?>>
                    <?=htmlspecialchars($role['nom'])?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label for="statut">Statut :</label>
              <select id="statut" name="statut" class="form-control" required>
                <option value="actif" <?=($utilisateur['statut'] == 'actif') ? 'selected' : ''?>>Actif</option>
                <option value="inactif" <?=($utilisateur['statut'] == 'inactif') ? 'selected' : ''?>>Inactif</option>
                <option value="sanctionne" <?=($utilisateur['statut'] == 'sanctionne') ? 'selected' : ''?>>Sanctionné</option>
              </select>
            </div>

            <div class="form-actions">
              <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i> Modifier
              </button>
              <a href="gestion_utilisateurs.php" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Retour
              </a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script src="../templatemo_455_visual_admin/js/jquery-1.11.2.min.js"></script>
  <script src="../templatemo_455_visual_admin/js/bootstrap-filestyle.min.js"></script>
  <script src="../templatemo_455_visual_admin/js/templatemo-script.js"></script>
</body>
</html>
