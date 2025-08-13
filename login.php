<?php
require_once 'config.php';
require_once 'utilisateurs.php';
require_once 'auth.php';

$utilisateurs = new Utilisateurs($pdo);

if ($utilisateurs->superAdminExiste() === false) {
    header('Location: super_admin_creation.php');
    exit;
}

$errors = [];
$success = [];

// Traitement de l'inscription étudiant
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $prenom = trim($_POST['prenom'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $motDePasse = $_POST['mot_de_passe'] ?? '';
    $confirmMotDePasse = $_POST['confirm_mot_de_passe'] ?? '';

    // Validation
    if (!$prenom || !$nom || !$email || !$motDePasse || !$confirmMotDePasse) {
        $errors[] = "Veuillez remplir tous les champs.";
    } elseif ($motDePasse !== $confirmMotDePasse) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($motDePasse) < 6) {
        $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'email n'est pas valide.";
    } else {
        // Vérifier si l'email existe déjà
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Cet email est déjà utilisé.";
        } else {
            // Créer le compte étudiant
            // Récupérer l'ID du rôle étudiant
            $stmt = $pdo->prepare("SELECT id FROM roles WHERE nom = 'etudiant'");
            $stmt->execute();
            $role = $stmt->fetch();
            
            if ($role) {
                $role_id = $role['id'];
                $user_id = $utilisateurs->creerUtilisateur($nom, $prenom, $email, $motDePasse, $role_id, false);
                if ($user_id) {
                    $success[] = "Compte étudiant créé avec succès ! Vous pouvez maintenant vous connecter.";
                } else {
                    $errors[] = "Erreur lors de la création du compte. Veuillez réessayer.";
                }
            } else {
                $errors[] = "Erreur: rôle étudiant non trouvé.";
            }
        }
    }
}

// Traitement de la connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $motDePasse = $_POST['mot_de_passe'] ?? '';

    if (!$email || !$motDePasse) {
        $errors[] = "Veuillez remplir tous les champs.";
    } else {
        $user = $utilisateurs->authentifier($email, $motDePasse);
        if ($user) {
            Auth::login($user);
            header('Location: dashboard.php');
            exit;
        } else {
            $errors[] = "Email ou mot de passe incorrect, ou compte inactif.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion - PlagiaTrack</title>
    <link href="../templatemo_455_visual_admin/css/bootstrap.min.css" rel="stylesheet">
    <link href="../templatemo_455_visual_admin/css/font-awesome.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #ffffffff 0%, #018376ff 100%);
            //#0deeeeff
            font-family: Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            margin: 20px;
            overflow: hidden;
        }
        .login-header {
            background: #007bff;
            color: white;
            padding: 30px;
            text-align: center;
        }
        .login-header h1 {
            margin: 0;
            font-size: 24px;
        }
        .login-form {
            padding: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-control {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 12px;
            width: 100%;
            box-sizing: border-box;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
        }
        .btn-primary {
            background: #007bff;
            color: white;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
            margin-top: 10px;
        }
        .error-list, .success-list {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .error-list {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .success-list {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
        }
        .tab {
            flex: 1;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            background: #f8f9fa;
            border: none;
        }
        .tab.active {
            background: white;
            border-bottom: 2px solid #007bff;
        }
        .tab-content {
            display: none;
            padding: 30px;
        }
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>PlagiaTrack</h1>
            <p>Système de Détection de Plagiat</p>
        </div>

        <?php if ($errors): ?>
            <div class="error-list">
                <?php foreach ($errors as $error): ?>
                    <div><?=htmlspecialchars($error)?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-list">
                <?php foreach ($success as $success_msg): ?>
                    <div><?=htmlspecialchars($success_msg)?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="tabs">
            <button class="tab active" onclick="showTab('login')">Connexion</button>
            <button class="tab" onclick="showTab('register')">Créer un compte Étudiant</button>
        </div>

        <div class="tab-content active" id="login-tab">
            <form method="post" action="">
                <input type="hidden" name="login" value="1">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" class="form-control" name="email" required>
                </div>
                <div class="form-group">
                    <label>Mot de passe</label>
                    <input type="password" class="form-control" name="mot_de_passe" required>
                </div>
                <button type="submit" class="btn btn-primary">Se connecter</button>
            </form>
        </div>

        <div class="tab-content" id="register-tab">
            <form method="post" action="">
                <input type="hidden" name="register" value="1">
                <div class="form-group">
                    <label>Prénom</label>
                    <input type="text" class="form-control" name="prenom" required>
                </div>
                <div class="form-group">
                    <label>Nom</label>
                    <input type="text" class="form-control" name="nom" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" class="form-control" name="email" required>
                </div>
                <div class="form-group">
                    <label>Mot de passe</label>
                    <input type="password" class="form-control" name="mot_de_passe" required>
                </div>
                <div class="form-group">
                    <label>Confirmer le mot de passe</label>
                    <input type="password" class="form-control" name="confirm_mot_de_passe" required>
                </div>
                <button type="submit" class="btn btn-primary">Créer mon compte</button>
            </form>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
