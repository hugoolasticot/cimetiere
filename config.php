<?php
// config.php - Configuration de la base de données
$host = 'localhost';
$dbname = 'cimetiere';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Démarrer la session
session_start();

// Fonction pour vérifier si l'utilisateur est connecté
function estConnecte() {
    return isset($_SESSION['utilisateur_id']);
}

// Fonction pour vérifier si l'utilisateur est admin
function estAdmin() {
    return isset($_SESSION['type_compte']) && $_SESSION['type_compte'] === 'admin';
}

// Fonction pour vérifier si l'utilisateur est famille
function estFamille() {
    return isset($_SESSION['type_compte']) && $_SESSION['type_compte'] === 'famille';
}

// Fonction pour rediriger si non connecté
function verifierConnexion() {
    if (!estConnecte()) {
        header('Location: login.php');
        exit;
    }
}

// Fonction pour rediriger si non admin
function verifierAdmin() {
    verifierConnexion();
    if (!estAdmin()) {
        header('Location: dashboard.php');
        exit;
    }
}
?>