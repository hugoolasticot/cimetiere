<?php
// Script pour générer les mots de passe hachés corrects
// À exécuter une seule fois pour mettre à jour la base de données

// Mots de passe en clair
$passwords = [
    'admin123',
    'famille123'
];

echo "-- Script SQL pour mettre à jour les mots de passe avec le bon hachage\n\n";

foreach ($passwords as $password) {
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    echo "-- Mot de passe '$password' haché : $hashed\n";
}

echo "\n-- Commandes SQL à exécuter :\n\n";

$admin_hash = password_hash('admin123', PASSWORD_DEFAULT);
$famille_hash = password_hash('famille123', PASSWORD_DEFAULT);

echo "-- Mise à jour du mot de passe admin\n";
echo "UPDATE utilisateurs SET mot_de_passe = '$admin_hash' WHERE nom_utilisateur = 'admin';\n\n";

echo "-- Mise à jour des mots de passe famille\n";
echo "UPDATE utilisateurs SET mot_de_passe = '$famille_hash' WHERE type_compte = 'famille';\n\n";

echo "-- Ou exécutez ces commandes individuellement :\n";
$comptes_famille = ['jean_dupont', 'lucie_martin', 'paul_bernard', 'sophie_robert', 'nicolas_morel'];
foreach ($comptes_famille as $compte) {
    echo "UPDATE utilisateurs SET mot_de_passe = '$famille_hash' WHERE nom_utilisateur = '$compte';\n";
}
?>