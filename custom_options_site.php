<?php
/**
 * Plugin Name: Options du site
 * Description: Les options du site
 * Version: 1.0.1
 */

//Plugin personalisé qui permet d'activer un mose attente et redirige vers une page selectionné par l'utilisateur.
//Le plugin permet aussi d'exclure des pages et des types de publications à exclure de la redirection.

// Pour ajouter dans le menu du back office
add_action('admin_menu', 'custom_activate_page_attente_menu');

function custom_activate_page_attente_menu()
{
    add_menu_page(
        'Options du site', // Titre de l'onglet
        'Options du site', // Nom dans le menu
        'manage_options', // Niveau de droit requis pour accéder au menu
        'custom_activate_page_attente_settings', // Slug de la page
        'custom_activate_page_attente_settings_page', // Fonction de rappel pour afficher la page
        'dashicons-admin-generic', // Icône dans le menu
        99 // Position dans le menu
    );
}

// Redirection vers la page d'accueil si le mode attente est activé
add_action('template_redirect', 'custom_activate_page_attente_redirect');

#récuperer l'adresse ip de l'utilisateur
function get_ip_address()
{
    // vérifier si l'adresse ip est partagée
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    // vérifier si l'adresse ip est un proxy
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    // sinon : c'est l'adresse ip de l'utilisateur
    else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

function custom_activate_page_attente_redirect()
{
    //récupérer l'adresse ip de l'utilisateur
    $monIp = get_ip_address();

    //récupérer les adresses ip à exclure
    $excluded_ip =  array_map('trim',explode(PHP_EOL,get_option('custom_add_ip')));

    $enable_mode_attente = get_option('custom_activate_page_attente_enable', false);

    //vérifier si l'adresse ip de l'utilisateur est dans la liste des adresses ip à exclure et si le mode attente est activé
    if ($enable_mode_attente && !in_array($monIp, $excluded_ip))
    {
        // Récupérer tous les types de post
        $all_post_types = array_keys(get_post_types(array('public' => true), 'objects')); 

        // Récupérer l'ID de la page d'accueil en mode attente
        $page_accueil_id = get_option('custom_activate_page_attente_page_accueil', 0);

        // Récupérer les pages exclues des redirections
        $excluded_pages = get_option('custom_activate_page_attente_excluded_pages', array());

        // Récupérer les articles exclus des redirections
        $excluded_post_types = get_option('custom_activate_page_attente_excluded_posts', array());

        // Vérifier si la page courante est la page d'accueil en mode attente

        $redirect = false;
        if($page_accueil_id != get_the_ID())
        {
            $current_post_id = get_the_ID();
            $current_post_type = get_post_type($current_post_id);
            
            if($current_post_type == 'page' && !in_array($current_post_id, $excluded_pages))
            {
                $redirect = true;
            }
            elseif($current_post_type != 'page' && !in_array($current_post_type,$excluded_post_types))
            {
                $redirect = true;
            }
            if($redirect)
            {
                wp_redirect(get_permalink($page_accueil_id));
                exit;
            }
        }
    }
}
function custom_activate_page_attente_additional_options_tab_content()
{
    ?>
    <div class="wrap">
        <h2>Options supplémentaires</h2>
        <h3>Ceci est le contenu de l'onglet Options supplémentaires.</h3>
        <p>Ajoutez ici les formulaires ou les paramètres supplémentaires </p>
    </div>
    <?php
}

// Fonction de rappel pour afficher la page de réglages
function custom_activate_page_attente_general_options_tab_content()
{
    // Récupérer les valeurs des options
    $enable_mode_attente = get_option('custom_activate_page_attente_enable', false);
    $page_accueil_id = get_option('custom_activate_page_attente_page_accueil', 0);
    $excluded_pages = get_option('custom_activate_page_attente_excluded_pages', array());
    $excluded_posts = get_option('custom_activate_page_attente_excluded_posts', array());
    $excluded_ip = get_option('custom_add_ip', '');

    //var_dump($excluded_ip);exit;
    $explode_ip = explode(" ", $excluded_ip);
    //var_dump($explode_ip);exit;

    // Récupérer la liste des pages
    $pages = get_pages();

    // Récupérer la liste des types de publications
    $post_types = get_post_types(array('public' => true), 'objects');

    // Affichage du formulaire de réglages pour les options générales
    ?>
    <div class="wrap">
        <h1>Page d'attente</h1>
        <form method="post" action="">
            <table class="form-table">
                <tr>
                    <p>L'activation du mode attente redirige toutes publications vers la page d'accueil sélectionnée.<br>Les options de cette page permettent de définir les types de contenus qui doivent rester accessibles.</p>
                    <th scope="row">Activer le mode attente</th>
                    <td><input type="checkbox" name="custom_activate_page_attente_enable" <?php checked($enable_mode_attente, true); ?> /></td>
                </tr>
                <tr>
                    <th scope="row">Adresse IP à exclure de la redirection (une adresse IP par ligne, #adresse IP pour commenter)</th>
                    <td>
                        <textarea   cols="50" rows="6" wrap="hard" name="custom_add_ip"><?php echo implode(PHP_EOL, $explode_ip); ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Page d'accueil en mode attente</th>
                    <td>
                        <select name="custom_activate_page_attente_page_accueil">
                            <option value="0">Sélectionnez une page</option>
                            <?php foreach ($pages as $page) : ?>
                                <option value="<?php echo $page->ID; ?>" <?php selected($page->ID, $page_accueil_id); ?>><?php echo $page->post_title; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Types de publications restant accessibles en mode attente</th>
                    <td>
                        <?php foreach ($post_types as $post_type) : ?>
                           <label><input type="checkbox" name="custom_activate_page_attente_excluded_posts[]" value="<?php echo $post_type->name; ?>" <?php if (in_array($post_type->name, $excluded_posts)) echo 'checked'; ?> <?php if ($post_type->name === 'page') echo 'disabled'; ?>/><?php echo $post_type->labels->menu_name ?></label><br>
                        <?php endforeach; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Pages restant accessibles en mode attente</th>
                    <td>
                        <?php foreach ($pages as $page) : ?>
                            <label><input type="checkbox" name="custom_activate_page_attente_excluded_pages[]" value="<?php echo $page->ID; ?>" <?php if (in_array($page->ID, $excluded_pages)) echo 'checked'; ?> /><?php echo $page->post_title; ?></label><br>
                        <?php endforeach; ?>
                    </td>
                </tr>
            </table>
            <p class="submit"><input type="submit" name="custom_activate_page_attente_submit" class="button-primary" value="Enregistrer les réglages"></p>
        </form>
    </div>
    <?php
}

function custom_activate_page_attente_settings_page()
{
    // Vérifier si le formulaire a été soumis
    if (isset($_POST['custom_activate_page_attente_submit'])) 
    {
        // Récupérer les valeurs des champs pour les options page d'attente
        $enable_mode_attente = isset($_POST['custom_activate_page_attente_enable']) ? true : false;
        $page_accueil_id = isset($_POST['custom_activate_page_attente_page_accueil']) ? intval($_POST['custom_activate_page_attente_page_accueil']) : 0;
        $excluded_pages = isset($_POST['custom_activate_page_attente_excluded_pages']) ? $_POST['custom_activate_page_attente_excluded_pages'] : array();
        $excluded_posts = isset($_POST['custom_activate_page_attente_excluded_posts']) ? $_POST['custom_activate_page_attente_excluded_posts'] : array();
        $excluded_ip = isset($_POST['custom_add_ip']) ? $_POST['custom_add_ip'] : '';


        // Enregistrer les valeurs dans les options pour les options page d'attente
        update_option('custom_activate_page_attente_enable', $enable_mode_attente);
        update_option('custom_activate_page_attente_page_accueil', $page_accueil_id);
        update_option('custom_activate_page_attente_excluded_pages', $excluded_pages);
        update_option('custom_activate_page_attente_excluded_posts', $excluded_posts);
        update_option('custom_add_ip', $excluded_ip);


        // Afficher un message de succès
        echo '<div class="notice notice-success is-dismissible"><p>Les réglages ont été enregistrés avec succès.</p></div>';
    }
    $current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general-options';

    ?>
    <div class="wrap">
        <h1>Options du site</h1>
        <h2 class="nav-tab-wrapper">
            <a href="?page=custom_activate_page_attente_settings&tab=general-options" class="nav-tab <?php echo ($current_tab == 'general-options') ? 'nav-tab-active' : ''; ?>">Page d'attente</a>
<!--            <a href="?page=custom_activate_page_attente_settings&tab=additional-options" class="nav-tab --><?php //echo ($current_tab == 'additional-options') ? 'nav-tab-active' : ''; ?><!--">Options supplémentaires</a>-->
        </h2>
        <?php
        // Affiche le contenu de l'onglet correspondant
        if ($current_tab == 'general-options') {
            custom_activate_page_attente_general_options_tab_content();
        } elseif ($current_tab == 'additional-options') {
            custom_activate_page_attente_additional_options_tab_content();
        }
        ?>
    </div>
    <?php
}



