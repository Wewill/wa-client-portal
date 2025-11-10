# Guide de Debug pour les Traductions WACP

## Problème
Les traductions françaises fonctionnent sur un site mais pas sur un autre.

## Solution de Debug Implémentée

J'ai ajouté un système complet de logs de debug pour identifier le problème de traduction.

### Modifications effectuées :

1. **[class-wa-client-portal-i18n.php](includes/class-wa-client-portal-i18n.php)** - Ajout de logs détaillés dans la méthode `load_plugin_textdomain()`
2. **[class-wa-client-portal-i18n-debug.php](admin/class-wa-client-portal-i18n-debug.php)** - Nouvelle page d'administration pour visualiser les logs
3. **[class-wa-client-portal-admin.php](admin/class-wa-client-portal-admin.php)** - Chargement de la page de debug

## Comment utiliser le système de debug

### Étape 1 : Activer WP_DEBUG

Dans votre fichier `wp-config.php`, ajoutez ou modifiez ces lignes :

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
```

### Étape 2 : Accéder à la page de debug

1. Connectez-vous à l'administration WordPress
2. Allez dans **Outils > WACP i18n Debug**
3. Vous verrez une page complète avec :
   - Configuration actuelle (locale, chemins, etc.)
   - État des fichiers de traduction (.mo, .po, .l10n.php)
   - Test de traduction en temps réel
   - Domaines de texte chargés
   - **Logs détaillés** du chargement des traductions

### Étape 3 : Générer les logs

1. Rechargez n'importe quelle page du site (front-end ou admin)
2. Retournez sur la page **Outils > WACP i18n Debug**
3. Consultez la section "Translation Loading Logs"

### Étape 4 : Analyser les logs

Les logs vous indiqueront :
- ✅ La locale détectée
- ✅ Le chemin vers les fichiers de traduction
- ✅ Si les fichiers .mo existent et sont lisibles
- ✅ Si le chargement a réussi ou échoué
- ✅ Un test de traduction
- ✅ Si le domaine "wacp" est bien chargé dans WordPress

### Étape 5 : Comparer les deux sites

1. Faites cette procédure sur **le site qui fonctionne**
2. Prenez note des logs (ou faites une capture d'écran)
3. Faites la même chose sur **le site qui ne fonctionne pas**
4. Comparez les logs pour identifier la différence

## Points à vérifier

### Différences possibles entre les sites :

1. **Locale différente**
   - Un site en `fr_FR`, l'autre en `fr_CA` ou autre
   - Solution : Vérifier dans Réglages > Général > Langue du site

2. **Fichiers .mo manquants ou différents**
   - Le fichier .mo n'existe pas sur l'autre site
   - Solution : Copier les fichiers du dossier `languages/`

3. **Permissions de fichiers**
   - Le serveur web ne peut pas lire le fichier .mo
   - Solution : Vérifier les permissions (chmod 644)

4. **Cache WordPress ou plugin de cache**
   - Les traductions sont en cache
   - Solution : Vider tous les caches

5. **Hook de chargement trop tardif**
   - Un thème ou plugin charge du contenu avant que les traductions soient chargées
   - Les logs montreront si le domaine est chargé ou non

6. **Version de PHP ou WordPress différente**
   - WordPress 6.5+ utilise `.l10n.php` en priorité
   - Solution : S'assurer que le fichier `.l10n.php` est présent

## Actions correctives possibles

En fonction des logs, voici les actions à entreprendre :

### Si le fichier .mo n'est pas trouvé :
```bash
# Copier les fichiers de traduction
cp -r languages/ /path/to/other/site/wp-content/plugins/wa-client-portal/
```

### Si les permissions sont incorrectes :
```bash
chmod 644 languages/*.mo
chmod 644 languages/*.po
chmod 644 languages/*.l10n.php
```

### Si la locale est différente :
- Aller dans WordPress Admin > Réglages > Général
- Changer la "Langue du site" pour correspondre à celle qui fonctionne

### Si le domaine n'est pas chargé :
- Vérifier que le hook `plugins_loaded` est bien exécuté
- Si besoin, on peut modifier le code pour charger les traductions plus tôt (hook `init`)

## Désactiver les logs

Une fois le problème résolu, vous pouvez :

1. Désactiver WP_DEBUG dans `wp-config.php`
2. Cliquer sur "Clear Logs" dans la page de debug
3. Les logs ne seront plus générés automatiquement

## Contact

Si les logs ne vous permettent pas d'identifier le problème, envoyez-moi :
- Les logs du site qui fonctionne
- Les logs du site qui ne fonctionne pas
- La version de WordPress sur les deux sites
- La locale configurée sur les deux sites

Je pourrai alors vous aider à identifier la cause exacte du problème.
