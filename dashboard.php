<?php
require_once 'config.php';
verifierConnexion();

// Récupération des informations de l'utilisateur connecté
$nom_utilisateur = $_SESSION['nom_utilisateur'];
$type_compte = $_SESSION['type_compte'];
$id_heritier = $_SESSION['id_heritier'];

// Données spécifiques selon le type de compte
$donnees_admin = [];
$donnees_famille = [];

if (estAdmin()) {
    // Statistiques pour l'admin
    try {
        // Nombre total d'occupants
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM occupant");
        $donnees_admin['total_occupants'] = $stmt->fetch()['total'];
        
        // Nombre total d'emplacements
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM emplacement");
        $donnees_admin['total_emplacements'] = $stmt->fetch()['total'];
        
        // Valeur totale des biens
        $stmt = $pdo->query("SELECT SUM(valeurs) as total FROM biens WHERE valeurs < 2147483647");
        $donnees_admin['valeur_biens'] = $stmt->fetch()['total'] ?? 0;
        
        // Concessions expirant bientôt (dans les 365 jours)
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM concession WHERE date_expiration BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 365 DAY)");
        $donnees_admin['concessions_expiration'] = $stmt->fetch()['total'];
        
        // Récents décès (dernière semaine)
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM occupant WHERE date_mort >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
        $donnees_admin['recents_deces'] = $stmt->fetch()['total'];
        
    } catch (PDOException $e) {
        $erreur = "Erreur lors de la récupération des données administrateur.";
    }
    
} elseif (estFamille()) {
    // Données pour les familles
    try {
        // Informations sur l'héritier
        $stmt = $pdo->prepare("SELECT * FROM heritier WHERE id_heritier = ?");
        $stmt->execute([$id_heritier]);
        $donnees_famille['heritier'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Concessions de la famille
        $stmt = $pdo->prepare("
            SELECT c.*, e.Allee, e.type, o.nom, o.prenom, o.date_mort 
            FROM concession c
            JOIN emplacement e ON c.id_emplacement = e.id_emplacement  
            JOIN occupant o ON c.id_occupant = o.id_occupant
            JOIN acheteur a ON c.id_acheteur = a.id_acheteur
            WHERE a.id_heritier = ?
        ");
        $stmt->execute([$id_heritier]);
        $donnees_famille['concessions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Biens de la famille
        $stmt = $pdo->prepare("
            SELECT b.*, o.nom, o.prenom 
            FROM biens b
            JOIN appartient ap ON b.id_biens = ap.id_biens
            JOIN occupant o ON ap.id_occupant = o.id_occupant
            JOIN concession c ON o.id_occupant = c.id_occupant
            JOIN acheteur a ON c.id_acheteur = a.id_acheteur
            WHERE a.id_heritier = ?
        ");
        $stmt->execute([$id_heritier]);
        $donnees_famille['biens'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        $erreur = "Erreur lors de la récupération des données famille.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - Gestion Cimetière</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 1.5rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
        }
        
        .logout-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 0.5rem 1rem;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            color: #333;
            margin-bottom: 0.5rem;
            font-size: 2rem;
        }
        
        .stat-card p {
            color: #666;
        }
        
        .section {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .section h2 {
            color: #333;
            margin-bottom: 1rem;
            border-bottom: 2px solid #667eea;
            padding-bottom: 0.5rem;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .table th,
        .table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        .table tr:hover {
            background: #f8f9fa;
        }
        
        .admin-nav {
            margin-bottom: 2rem;
        }
        
        .nav-links {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .nav-link {
            background: #667eea;
            color: white;
            padding: 0.75rem 1.5rem;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .nav-link:hover {
            background: #5a67d8;
        }
        
        .no-data {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 2rem;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Système de Gestion du Cimetière</h1>
        <div class="user-info">
            <span>Bienvenue, <?php echo htmlspecialchars($nom_utilisateur); ?></span>
            <span class="badge"><?php echo ucfirst($type_compte); ?></span>
            <a href="logout.php" class="logout-btn">Déconnexion</a>
        </div>
    </div>
    
    <div class="container">
        <?php if (estAdmin()): ?>
            <!-- Interface Administrateur -->
            <div class="admin-nav">
                <div class="nav-links">
                    <a href="gestion_utilisateurs.php" class="nav-link">Gestion Utilisateurs</a>
                    <a href="gestion_emplacements.php" class="nav-link">Gestion Emplacements</a>
                    <a href="gestion_concessions.php" class="nav-link">Gestion Concessions</a>
                    <a href="rapports.php" class="nav-link">Rapports</a>
                </div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h3><?php echo $donnees_admin['total_occupants']; ?></h3>
                    <p>Total Occupants</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $donnees_admin['total_emplacements']; ?></h3>
                    <p>Total Emplacements</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo number_format($donnees_admin['valeur_biens']); ?> €</h3>
                    <p>Valeur des Biens</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $donnees_admin['concessions_expiration']; ?></h3>
                    <p>Concessions à Renouveler</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $donnees_admin['recents_deces']; ?></h3>
                    <p>Décès Récents (7j)</p>
                </div>
            </div>
            
        <?php elseif (estFamille()): ?>
            <!-- Interface Famille -->
            <div class="section">
                <h2>Informations Famille</h2>
                <?php if ($donnees_famille['heritier']): ?>
                    <p><strong>Héritier :</strong> <?php echo htmlspecialchars($donnees_famille['heritier']['prenom'] . ' ' . $donnees_famille['heritier']['nom_heritier']); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="section">
                <h2>Vos Concessions</h2>
                <?php if (empty($donnees_famille['concessions'])): ?>
                    <div class="no-data">Aucune concession trouvée.</div>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Défunt</th>
                                <th>Emplacement</th>
                                <th>Type</th>
                                <th>Date Décès</th>
                                <th>Durée</th>
                                <th>Expiration</th>
                                <th>Prix</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($donnees_famille['concessions'] as $concession): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($concession['prenom'] . ' ' . $concession['nom']); ?></td>
                                    <td><?php echo htmlspecialchars($concession['Allee']); ?></td>
                                    <td><?php echo htmlspecialchars($concession['type']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($concession['date_mort'])); ?></td>
                                    <td><?php echo $concession['duree']; ?> ans</td>
                                    <td><?php echo date('d/m/Y', strtotime($concession['date_expiration'])); ?></td>
                                    <td><?php echo number_format($concession['prix'], 2); ?> €</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <div class="section">
                <h2>Biens Associés</h2>
                <?php if (empty($donnees_famille['biens'])): ?>
                    <div class="no-data">Aucun bien enregistré.</div>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Défunt</th>
                                <th>Description</th>
                                <th>Valeur</th>
                                <th>Photo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($donnees_famille['biens'] as $bien): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($bien['prenom'] . ' ' . $bien['nom']); ?></td>
                                    <td><?php echo htmlspecialchars($bien['description']); ?></td>
                                    <td>
                                        <?php 
                                        if ($bien['valeurs'] >= 2147483647) {
                                            echo "Valeur exceptionnelle";
                                        } else {
                                            echo number_format($bien['valeurs']) . ' €';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo $bien['photo'] ? 'Oui' : 'Non'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
        <?php endif; ?>
    </div>
</body>
</html>