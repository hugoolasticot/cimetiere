<?php
require_once 'config.php';

$erreur = '';

// Si l'utilisateur est déjà connecté, rediriger vers le dashboard
if (estConnecte()) {
    header('Location: dashboard.php');
    exit;
}

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_utilisateur = trim($_POST['nom_utilisateur'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';
    
    if (empty($nom_utilisateur) || empty($mot_de_passe)) {
        $erreur = 'Veuillez remplir tous les champs.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id_utilisateur, nom_utilisateur, mot_de_passe, type_compte, id_heritier FROM utilisateurs WHERE nom_utilisateur = ? AND actif = 1");
            $stmt->execute([$nom_utilisateur]);
            $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($utilisateur && password_verify($mot_de_passe, $utilisateur['mot_de_passe'])) {
                // Connexion réussie
                $_SESSION['utilisateur_id'] = $utilisateur['id_utilisateur'];
                $_SESSION['nom_utilisateur'] = $utilisateur['nom_utilisateur'];
                $_SESSION['type_compte'] = $utilisateur['type_compte'];
                $_SESSION['id_heritier'] = $utilisateur['id_heritier'];
                
                header('Location: dashboard.php');
                exit;
            } else {
                $erreur = 'Nom d\'utilisateur ou mot de passe incorrect.';
            }
        } catch (PDOException $e) {
            $erreur = 'Erreur de connexion à la base de données.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Gestion Cimetière</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header h1 {
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            color: #666;
            font-size: 0.9rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn-login {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
        }
        
        .error-message {
            background: #fee;
            color: #c33;
            padding: 0.75rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            border: 1px solid #fcc;
        }
        
        .credentials-info {
            margin-top: 2rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 5px;
            font-size: 0.85rem;
        }
        
        .credentials-info h3 {
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .credentials-info p {
            color: #666;
            margin-bottom: 0.25rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Connexion</h1>
            <p>Système de Gestion du Cimetière</p>
        </div>
        
        <?php if ($erreur): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($erreur); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="nom_utilisateur">Nom d'utilisateur :</label>
                <input type="text" id="nom_utilisateur" name="nom_utilisateur" 
                       value="<?php echo htmlspecialchars($_POST['nom_utilisateur'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="mot_de_passe">Mot de passe :</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" required>
            </div>
            
            <button type="submit" class="btn-login">Se connecter</button>
        </form>
        
        <div class="credentials-info">
            <h3>Comptes de test :</h3>
            <p><strong>Admin :</strong> admin / admin123</p>
            <p><strong>Famille :</strong> jean_dupont / famille123</p>
            <p><strong>Famille :</strong> lucie_martin / famille123</p>
            <p><strong>Famille :</strong> paul_bernard / famille123</p>
            <p><strong>Famille :</strong> sophie_robert / famille123</p>
            <p><strong>Famille :</strong> nicolas_morel / famille123</p>
        </div>
    </div>
</body>
</html>