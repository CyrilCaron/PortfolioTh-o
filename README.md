# TF Production · site de Théo Fasquel

Site vitrine en PHP (sans base de données) : accueil, portfolio, pages projet et page d'administration.

## Mise en ligne
Copier tout le dossier sur un hébergement PHP 8.0 ou plus (Apache : les fichiers `.htaccess` gèrent le cache et protègent `contenu/`).
Ouvrir ensuite `admin.php` tout de suite pour créer le mot de passe de l'admin.

## Organisation
- `index.php`, `portfolio.php`, `projet.php?p=<slug>`, `mentions-legales.php` : pages du site
- `admin.php` : création des projets, envoi des photos, mise en page par blocs
- `contenu/projets/*.json` : un fichier par projet (écrit par l'admin)
- `assets/img/<projet>/` : photos en WebP, 3 tailles (`001.webp`, `001-m.webp`, `001-s.webp`)
- `partials/` : en-tête, pied de page, transition, données communes (`data.php`)
- Nouvelle page : copier `mentions-legales.php`, la transition et le menu sont ajoutés automatiquement.

## Mise en ligne sur Vercel
Le site tourne sur Vercel grâce au runtime communautaire `vercel-php` (voir `vercel.json`) :
toutes les pages passent par `api/index.php`, qui exécute la page PHP demandée.
`partials/` et `contenu/` ne sont pas accessibles depuis le navigateur.

1. Sur vercel.com : **Add New → Project**, importer ce dépôt, préréglage **Other**, puis **Deploy**.
2. Domaine : **Settings → Domains**, ajouter `tf-production.fr`, puis recopier chez Ionos les enregistrements DNS indiqués par Vercel.
3. Chaque modification de `main` est mise en ligne automatiquement.

Sur Vercel, les fichiers du serveur sont en lecture seule : l'admin affiche pour l'instant un message,
et les projets s'ajoutent dans le dépôt (`contenu/projets/` + `assets/img/<projet>/`).
