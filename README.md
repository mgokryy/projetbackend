Membres du Groupe : 
- Marine EL OSTA
- Marie-Grace OKRY

  Version de symfony : 6.4.x  
  Version de Composer : 2.8.12

  
Installer les dépendances PHP avec Composer:

```json
composer install
```

Modifier la connexion a la base de donnees **si besoin** dans le fichier .env (verifier notement le port) 

Creer la Base de Donnees :
```json
php bin/console doctrine:database:create
```

Importer le fichier sql
  

```json
synfony serve:start
```


Mettre a jour la BDD :  
```json
php bin/console doctrine:schema:update --force
```
- Pour creer  un livre avec Postman :  
**POST** : http://127.0.0.1:8000/livre/add  

```json

{
    "titre": "Notre-Dame de Paris",
    "datePublication": "1831-01-01",
    "disponible": true,
    "idAuteur": 1,
    "categorie": 1
}
```  
- Get livre by id:  
**GET** : http://127.0.0.1:8000/livre/1  

- Get tous les livres :  
**GET** : http://127.0.0.1:8000/livres  

- Modifier un livre :  
**PUT** : http://127.0.0.1:8000/livre/edit/1  

```json
{
    "titre": "Les misérables",
    "datePublication": "1862-03-25",
    "disponible": 1,
    "idAuteur": 1,
    "categorie": 1
}
```

-Supprimer un livre  
**DELETE** : http://127.0.0.1:8000/livre/delete/1  


- Pour qu'un utilisateur emprunte un livre :     
**POST** http://127.0.0.1:8000/emprunts/add  
```json
{
    "utilisateur_id": 1,
    "livre_id": 1
}
```

- Pour qu'un utilisateur rende un livre :   
**PUT** http://127.0.0.1:8000/emprunts/rendre/1  
```json
{
    "utilisateur_id": 1,
    "livre_id": 1
}
```

- Pour savoir combien l'utilisateur a d'emprunts en cours:  
  **GET** : http://127.0.0.1:8000/emprunts/utilisateur/{id}  

- Pour tester l'emprunt de livre d'un auteur entre deux dates sur Postman (GET) :  
**GET** : http://127.0.0.1:8000/emprunts/livres-auteur?auteurId=1&dateDebut=2025-01-01&dateFin=2025-12-31  

**Paramètres:**  
auteurId  
dateDebut: Date de début (format: YYYY-MM-DD)  
dateFin: Date de fin (format: YYYY-MM-DD)  

