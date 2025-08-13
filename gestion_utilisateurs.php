<?php
require_once 'auth.php';
require_once 'utilisateurs.php';

if (!Auth::estConnecte()) {
    header('Location: login.php');
    exit;
}

$user = Auth::getUser();
$utilisateurs = new Utilisateurs($pdo);

// Vérifier que l'utilisateur est administrateur ou super admin
if (!in_array($user['role_id'], [1, 2])) { // 1 = super_admin, 2 = administrateur (à confirmer selon la base)
    echo "Accès refusé.";
    exit;
}

// Traitement des actions : ajouter, supprimer, modifier, réprimander
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'ajouter') {
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $motDePasse = $_POST['mot_de_passe'] ?? '';
        $role_id = intval($_POST['role_id'] ?? 0);

        if ($nom && $prenom && $email && $motDePasse && $role_id) {
            $utilisateurs->creerUtilisateur($nom, $prenom, $email, $motDePasse, $role_id);
            header('Location: gestion_utilisateurs.php');
            exit;
        } else {
            $error = "Tous les champs sont obligatoires.";
        }
    } elseif ($action === 'modifier') {
        $id = intval($_POST['id'] ?? 0);
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role_id = intval($_POST['role_id'] ?? 0);
        $statut = $_POST['statut'] ?? 'actif';

        if ($id && $nom && $prenom && $email && $role_id && $statut) {
            $utilisateurs->modifierUtilisateur($id, $nom, $prenom, $email, $role_id, $statut);
            header('Location: gestion_utilisateurs.php');
            exit;
        } else {
            $error = "Tous les champs sont obligatoires.";
        }
    }
} elseif ($action === 'supprimer') {
    $id = intval($_GET['id'] ?? 0);
    if ($id) {
        $utilisateurs->supprimerUtilisateur($id);
        header('Location: gestion_utilisateurs.php');
        exit;
    }
} elseif ($action === 'reprimander') {
    $id = intval($_GET['id'] ?? 0);
    if ($id) {
        $utilisateurs->reprimanderUtilisateur($id);
        header('Location: gestion_utilisateurs.php');
        exit;
    }
}

// Récupérer la liste des utilisateurs
$stmt = $pdo->query("SELECT u.*, r.nom AS role_nom FROM utilisateurs u JOIN roles r ON u.role_id = r.id ORDER BY u.id ASC");
$liste_utilisateurs = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM roles ORDER BY id ASC");
$liste_roles = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Gestion des utilisateurs - PlagiaTrack</title>
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
          <?php if ($user['role_id'] != 4): ?>
          <li><a href="../backend/gestion_utilisateurs.php" class="active"><i class="fa fa-users fa-fw"></i>GESTION DES UTILISATEURS</a></li>
          <?php endif; ?>
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
        <h1>Gestion des utilisateurs</h1>
        <?php if (!empty($error)): ?>
            <p style="color:red;"><?=htmlspecialchars($error)?></p>
        <?php endif; ?>

        <h2>Ajouter un utilisateur</h2>
        <form method="post" action="?action=ajouter">
            <label>Nom : <input type="text" name="nom" required></label><br />
            <label>Prénom : <input type="text" name="prenom" required></label><br />
            <label>Email : <input type="email" name="email" required></label><br />
            <label>Mot de passe : <input type="password" name="mot_de_passe" required></label><br />
            <label>Rôle :
                <select name="role_id" required>
                    <?php foreach ($liste_roles as $role): ?>
                        <option value="<?=htmlspecialchars($role['id'])?>"><?=htmlspecialchars($role['nom'])?></option>
                    <?php endforeach; ?>
                </select>
            </label><br />
            <button type="submit">Ajouter</button>
        </form>

        <h2>Liste des utilisateurs</h2>
        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>ID</th><th>Nom</th><th>Prénom</th><th>Email</th><th>Rôle</th><th>Statut</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($liste_utilisateurs as $utilisateur): ?>
                    <tr>
                        <td><?=htmlspecialchars($utilisateur['id'])?></td>
                        <td><?=htmlspecialchars($utilisateur['nom'])?></td>
                        <td><?=htmlspecialchars($utilisateur['prenom'])?></td>
                        <td><?=htmlspecialchars($utilisateur['email'])?></td>
                        <td><?=htmlspecialchars($utilisateur['role_nom'])?></td>
                        <td><?=htmlspecialchars($utilisateur['statut'])?></td>
                        <td>
                            <a href="?action=reprimander&id=<?=htmlspecialchars($utilisateur['id'])?>" onclick="return confirm('Réprimander cet utilisateur ?')">Réprimander</a> |
                            <a href="?action=supprimer&id=<?=htmlspecialchars($utilisateur['id'])?>" onclick="return confirm('Supprimer cet utilisateur ?')">Supprimer</a> |
                            <a href="modifier_utilisateur.php?id=<?=htmlspecialchars($utilisateur['id'])?>">Modifier</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

      </div>
    </div>
  </div>
  <script src="../templatemo_455_visual_admin/js/jquery-1.11.2.min.js"></script>
  <script src="../templatemo_455_visual_admin/js/jquery-migrate-1.2.1.min.js"></script>
  <script src="../templatemo_455_visual_admin/js/bootstrap-filestyle.min.js"></script>
  <script src="../templatemo_455_visual_admin/js/templatemo-script.js"></script>
</body>
</html>
