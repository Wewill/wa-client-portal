<?php
/*
Plugin Name: Export Membres RSFP
Description: Plugin pour exporter des membres avec des séparateurs personnalisés.
Version: 1.0
Author: Vous
*/

class Wa_Rsfp_Export {
    const ACTION = 'wa_rsfp_export_members';

    public function __construct() {
        // Ajouter le sous-menu dans le menu admin
        add_action('admin_menu', 					array($this, 'add_export_submenu') );
        // Enregistrer l'action pour l'exportation
        add_action('admin_post_' . self::ACTION, 	array($this, 'export'));
    }

    public function add_export_submenu() {
        add_submenu_page(
            'wa-client-portal-clients',
            '⎋ ' . __('Export members', 'wacp'),
            '⎋ ' . __('Export members', 'wacp'),
            'edit_others_posts',
            'wa_rsfp_export',
            array($this, 'render')
        );
    }

    public function render() {
        ?>
        <div class="wrap">
            <h1 class="wp-heading-title"><strong><?= __('Members', 'wacp') ?> > </strong><?= __('export', 'wacp') ?></h1>
            <form method="get" action="<?= esc_attr(admin_url('admin-post.php')) ?>">
                <input type="hidden" name="action" value="<?= self::ACTION ?>" />
                <div class="mta-field">
                    <label class="mta-field__label" for="mta-sep">Séparateur</label>
                    <select class="mta-field__input" id="mta-sep" name="separator">
                        <option value="semicolon">Point-virgule</option>
                        <option value="comma">Virgule</option>
                        <option value="tab">Tabulation</option>
                    </select>
                </div>
                <p class="mta-form__actions">
                    <button type="submit" class="button button-primary"><?= __('Export', 'wacp') ?></button>
                </p>
            </form>
        </div>
        <?php
    }

    public function export() {
        $prefix = 'wacp-';

        // Vérifier le séparateur choisi
        $separator = isset($_GET['separator']) ? $_GET['separator'] : 'semicolon';
        switch ($separator) {
            case 'comma':
                $separator = ',';
                break;
            case 'tab':
                $separator = "\t";
                break;
            default:
                $separator = ';';
                break;
        }

        // Récupérer les utilisateurs avec le rôle "subscriber"
        $users = get_users(array('role__in' => array('client-portal')));

        if (empty($users)) {
            error_log('WACP_Export:: Aucun utilisateur trouvé.');
        }

        // Entêtes du fichier CSV
        $headers = [
            __('ID', 'wacp'),
            __('Username', 'wacp'),
            __('Firstname', 'wacp'),
            __('Lastname', 'wacp'),
            __('E-mail', 'wacp'),

			__('Entity', 'wacp'),
			__('Media', 'wacp'),
			__('Phone', 'wacp'),
	
            __('Date', 'wacp'),
            __('Verified', 'wacp')
        ];

		// Output
		$output = implode($separator, $headers) . "\r\n";

		// Récupérer et ajouter les données des utilisateurs
        foreach ($users as $user) {

            $user_data = array(
                $user->ID,
                esc_html($user->user_login),
                esc_html($user->first_name),
                esc_html($user->last_name),
                esc_html($user->user_email),

				get_user_meta($user->ID, $prefix.'entity', true),
				get_user_meta($user->ID, $prefix.'media', true),
				get_user_meta($user->ID, $prefix.'phone', true),
			
                esc_html($user->user_registered),
				get_user_meta($user->ID, 'email_verification', true) ? 'Oui' : 'Non',
			);

            $output .= implode($separator, $user_data) . "\r\n";
        }

        // Définir les en-têtes HTTP pour le téléchargement du fichier CSV
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="exportMembers.csv"');
        echo "\xEF\xBB\xBF"; // UTF-8 BOM
        echo $output;
        exit;
    }
}

// Initialiser la classe
new Wa_Rsfp_Export();