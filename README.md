# Symfocook 
*Projet Symfony dans le cadre du Mastère 1 en Ingénierie du Web à l'ESGI de Lyon*

*Par Michalon Lisa, Duperthuy Hugo et Cauvet Louis*


## Objectif du projet
Notre objectif est de développer en Symphony un petit site de recettes, avec un système d'inscription/connexion d'utilisateurs.
Par défaut, le site doit proposer une liste de recettes variées, qu'on doit pouvoir filtrer par catégorie ou diffficulté.
On doit aussi pouvoir effectuer une recherche, afin d'obtenir les recettes dont le nom, la description ou les ingrédients correspondent à ce qui a été saisi.

De plus, un utilisateur connecté pourra créer une nouvelle recette sur la site, modifier les recettes qu'il a créé mais aussi ajouter des recettes dans ces favoris.

Afin, un utilisateur possédant le rôle admin bénéficiera d'un Back-Office depuis lequel il pourra modifier, ajouter ou supprimer toutes les recettes et les autres utilisateurs.


## Réalisation du projet

### 1) Création des fixtures *(par Louis Cauvet)*
Afin d'obtenir suffisament de données de tests, j'ai demandé à ChatGpt de me générer des fichiers JSON recensant une dizaine de catégories de recettes, 
240 ingrédients, 90 recettes et toutes les relations ingrédient-recette nécéssaires pour obtenir des données réalistes.

Une fois un résultat satisfaisant obtenu, et la bonne corrrepondance entre les données vérifiée, j'ai mis en place dans 
*"src/DataFixtures/AppFixtures.php"* plusieurs fonctions permettant de d'insérer ces données dans la base de données à partir des fichiers JSON.