<?php
/**
 * View: Renders the Choreographies tab.
 * This file contains the list view, single view, and the add-new modal.
 * @package DanceStudioApp
 */
if ( ! defined( 'WPINC' ) ) die;

if ( ! function_exists( 'dsa_render_choreographies_tab' ) ) {
    function dsa_render_choreographies_tab() {
        $action = $_GET['action'] ?? 'list';
        $choreography_id = isset($_GET['choreography_id']) ? absint($_GET['choreography_id']) : 0;

        if ( 'view' === $action && $choreography_id > 0 ) {
            dsa_render_single_choreography_view($choreography_id);
        } else {
            dsa_render_choreography_list_view();
        }
    }
}

if ( ! function_exists( 'dsa_render_choreography_list_view' ) ) {
    function dsa_render_choreography_list_view() {
        $orderby           = isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'title';
        $order             = isset( $_GET['order'] ) && strtolower($_GET['order']) === 'desc' ? 'DESC' : 'ASC';
        $difficulty_filter = isset( $_GET['difficulty_filter'] ) ? absint( $_GET['difficulty_filter'] ) : 0;
        $group_filter      = isset( $_GET['group_filter'] ) ? absint( $_GET['group_filter'] ) : 0;
        $paged             = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;

        $query_args = [
            'post_type'      => 'dsa_choreography',
            'posts_per_page' => 20,
            'orderby'        => $orderby,
            'order'          => $order,
            'paged'          => $paged,
        ];

        if ( $difficulty_filter > 0 ) {
            $query_args['tax_query'][] = [
                'taxonomy' => 'dsa_difficulty_level',
                'field'    => 'term_id',
                'terms'    => $difficulty_filter,
            ];
        }

        if ( $group_filter > 0 ) {
            $query_args['meta_query'][] = [
                'key'     => '_dsa_assigned_group_ids',
                'value'   => '"' . $group_filter . '"', // Searches for the ID serialized in an array
                'compare' => 'LIKE',
            ];
        }
        
        $choreographies_query = new WP_Query($query_args);
        ?>
        <h3>
            <?php esc_html_e( 'All Choreographies', 'dancestudio-app' ); ?>
            <button type="button" id="dsa-add-new-choreography-button" class="page-title-action"><?php esc_html_e('Add New (Pop-up)', 'dancestudio-app'); ?></button>
        </h3>
        
        <form method="get">
            <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>">
             <input type="hidden" name="repertoire_view" value="choreographies">

            <div class="tablenav top">
                <div class="alignleft actions">
                    <?php
                    wp_dropdown_categories([
                        'show_option_all' => __('All Difficulties', 'dancestudio-app'),
                        'taxonomy'        => 'dsa_difficulty_level',
                        'name'            => 'difficulty_filter',
                        'selected'        => $difficulty_filter,
                        'hierarchical'    => true,
                        'hide_empty'      => false,
                        'value_field'     => 'term_id',
                    ]);

                    $all_groups = get_posts(['post_type' => 'dsa_group', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC']);
                    echo '<select name="group_filter">';
                    echo '<option value="0">' . esc_html__('All Assigned Groups', 'dancestudio-app') . '</option>';
                    foreach ($all_groups as $group) {
                        echo '<option value="' . esc_attr($group->ID) . '" ' . selected($group_filter, $group->ID, false) . '>' . esc_html($group->post_title) . '</option>';
                    }
                    echo '</select>';
                    ?>
                    <input type="submit" class="button" value="<?php esc_attr_e('Filter'); ?>">
                </div>

                <?php
                $total_pages = $choreographies_query->max_num_pages;
                if ($total_pages > 1) {
                    $base_url = remove_query_arg('paged', $_SERVER['REQUEST_URI']);
                    echo '<div class="tablenav-pages"><span class="displaying-num">' . $choreographies_query->found_posts . ' items</span>';
                    echo paginate_links([
                        'base'      => $base_url . '%_%',
                        'format'    => '&paged=%#%',
                        'prev_text' => __('&laquo;'),
                        'next_text' => __('&raquo;'),
                        'total'     => $total_pages,
                        'current'   => $paged,
                    ]);
                    echo '</div>';
                }
                ?>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <?php dsa_render_sortable_table_header( __('Name', 'dancestudio-app'), 'title', $orderby, $order ); ?>
                        <th scope="col"><?php esc_html_e('Song', 'dancestudio-app'); ?></th>
                        <th scope="col"><?php esc_html_e('Difficulty', 'dancestudio-app'); ?></th>
                        <th scope="col"><?php esc_html_e('Assigned Group(s)', 'dancestudio-app'); ?></th>
                    </tr>
                </thead>
                <tbody id="the-list">
                <?php if ( $choreographies_query->have_posts() ) :
                    while ( $choreographies_query->have_posts() ) : $choreographies_query->the_post();
                        $choreography_id = get_the_ID();
                        $details = get_post_meta($choreography_id, '_dsa_choreography_details', true);
                        $song_display = !empty($details['song_title']) ? $details['song_title'] : '—';
                        $difficulty_terms = get_the_terms($choreography_id, 'dsa_difficulty_level');
                        $difficulty_display = !empty($difficulty_terms) ? esc_html($difficulty_terms[0]->name) : '—';
                        $assigned_group_ids = get_post_meta($choreography_id, '_dsa_assigned_group_ids', true);
                        $group_names = [];
                        if (!empty($assigned_group_ids) && is_array($assigned_group_ids)) {
                            foreach ($assigned_group_ids as $group_id) {
                                if ($group_title = get_the_title($group_id)) {
                                    $group_names[] = $group_title;
                                }
                            }
                        }
                        $groups_display = !empty($group_names) ? implode(', ', $group_names) : '—';
                        $view_link = add_query_arg(['page' => $_REQUEST['page'], 'repertoire_view' => 'choreographies', 'action' => 'view', 'choreography_id' => $choreography_id]);
                        $edit_link = get_edit_post_link($choreography_id);
                        $delete_link = get_delete_post_link($choreography_id, '', true);
                    ?>
                    <tr>
                        <td class="title column-title has-row-actions column-primary">
                            <strong><a class="row-title" href="<?php echo esc_url($view_link); ?>"><?php the_title(); ?></a></strong>
                            <div class="row-actions">
                                <span class="edit"><a href="<?php echo esc_url($edit_link); ?>"><?php esc_html_e('Edit'); ?></a> | </span>
                                <span class="view"><a href="<?php echo esc_url($view_link); ?>"><?php esc_html_e('View Details'); ?></a> | </span>
                                <span class="trash"><a href="<?php echo esc_url($delete_link); ?>" class="submitdelete" onclick="return confirm('<?php esc_attr_e('Are you sure you want to permanently delete this choreography?', 'dancestudio-app'); ?>');"><?php esc_html_e('Delete Permanently'); ?></a></span>
                            </div>
                        </td>
                        <td><?php echo esc_html($song_display); ?></td>
                        <td><?php echo $difficulty_display; ?></td>
                        <td><?php echo esc_html($groups_display); ?></td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr class="no-items"><td class="colspanchange" colspan="4"><?php esc_html_e('No choreographies found matching your criteria.', 'dancestudio-app'); ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </form>

        <?php wp_reset_postdata(); ?>
        
        <div id="dsa-add-choreography-modal" title="<?php esc_attr_e('Add New Choreography', 'dancestudio-app'); ?>" style="display:none;">
            <div id="dsa-modal-messages" class="notice" style="display:none; margin:0 0 15px;"></div>
            <form id="dsa-add-choreography-form">
                <table class="form-table">
                    <tr><th><label for="dsa_modal_post_title">Choreography Name *</label></th><td><input type="text" name="post_title" id="dsa_modal_post_title" class="widefat" required></td></tr>
                    <tr><th><label for="dsa_modal_content">Step Sheets</label></th><td><textarea name="content" id="dsa_modal_content" rows="5" class="widefat"></textarea></td></tr>
                    <tr><th><label for="dsa_modal_song_title">Song Title</label></th><td><input type="text" name="dsa_details[song_title]" id="dsa_modal_song_title" class="widefat"></td></tr>
                    <tr><th><label for="dsa_modal_song_artist">Song Artist</label></th><td><input type="text" name="dsa_details[song_artist]" id="dsa_modal_song_artist" class="widefat"></td></tr>
                    <tr><th><label for="dsa_modal_choreographer">Choreographer</label></th><td><input type="text" name="dsa_details[choreographer]" id="dsa_modal_choreographer" class="widefat"></td></tr>
                    <tr>
                        <th><label for="dsa_modal_difficulty_level">Difficulty</label></th>
                        <td><?php wp_dropdown_categories(['show_option_none' => __( '-- Select --', 'dancestudio-app' ), 'taxonomy' => 'dsa_difficulty_level', 'name' => 'dsa_difficulty_level', 'id' => 'dsa_modal_difficulty_level', 'hide_empty' => false, 'echo' => true]); ?></td>
                    </tr>
                    <tr><th><label for="dsa_modal_counts">Counts</label></th><td><input type="number" name="dsa_details[counts]" id="dsa_modal_counts" class="small-text"></td></tr>
                    <tr><th><label for="dsa_modal_walls">Walls</label></th><td><input type="number" name="dsa_details[walls]" id="dsa_modal_walls" class="small-text"></td></tr>
                    <tr><th><label for="dsa_modal_restarts">Restarts</label></th><td><input type="text" name="dsa_details[restarts]" id="dsa_modal_restarts" class="widefat"></td></tr>
                    <tr><th><label for="dsa_modal_sequence">Sequence</label></th><td><textarea name="dsa_details[sequence]" id="dsa_modal_sequence" rows="3" class="widefat"></textarea></td></tr>
                    <tr><th><label for="dsa_modal_song_file_url">Song File (MP3)</label></th><td><input type="text" id="dsa_modal_song_file_url" name="dsa_details[song_file_url]" class="widefat"><button type="button" class="button dsa-upload-song-button-modal" style="margin-top: 5px;">Upload</button></td></tr>
                    <tr><th><label for="dsa_modal_video_url">Video URL</label></th><td><input type="url" id="dsa_modal_video_url" name="dsa_details[video_url]" class="widefat" placeholder="https://youtube.com/..."></td></tr>
                    <tr>
                        <th>Assign to Group(s)</th>
                        <td><fieldset><?php
                            $all_groups_for_modal = get_posts(['post_type' => 'dsa_group', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC']);
                            if(empty($all_groups_for_modal)) { echo '<em>No groups created yet.</em>'; } 
                            else { foreach ($all_groups_for_modal as $group) { echo '<label style="display: block;"><input type="checkbox" name="dsa_assigned_groups[]" value="'.esc_attr($group->ID).'"> '.esc_html($group->post_title).'</label>'; } }
                        ?></fieldset></td>
                    </tr>
                </table>
            </form>
        </div>
        <?php
    }
}


if ( ! function_exists( 'dsa_render_single_choreography_view' ) ) {
    function dsa_render_single_choreography_view( $choreography_id ) {
        $post = get_post($choreography_id);
        if (!$post || $post->post_type !== 'dsa_choreography') {
            echo '<p>' . esc_html__('Choreography not found.', 'dancestudio-app') . '</p>';
            return;
        }

        $details = get_post_meta($choreography_id, '_dsa_choreography_details', true);
        $details = is_array($details) ? $details : [];
        $difficulty_terms = get_the_terms($choreography_id, 'dsa_difficulty_level');
        ?>
        <a href="<?php echo esc_url(remove_query_arg(['action', 'choreography_id'])); ?>">&larr; <?php esc_html_e('Back to All Choreographies', 'dancestudio-app'); ?></a>
        
        <h1 style="margin-top: 15px;">
            <?php echo esc_html($post->post_title); ?>
            <a href="<?php echo esc_url(get_edit_post_link($choreography_id)); ?>" class="page-title-action"><?php esc_html_e('Edit', 'dancestudio-app'); ?></a>
            <?php $delete_link = get_delete_post_link($choreography_id, '', true); ?>
            <a href="<?php echo esc_url($delete_link); ?>" class="page-title-action" style="color:#d63638;" onclick="return confirm('<?php esc_attr_e('Are you sure you want to permanently delete this choreography?', 'dancestudio-app'); ?>');">
                <?php esc_html_e('Delete Permanently', 'dancestudio-app'); ?>
            </a>
        </h1>

        <div class="dsa-choreography-details" style="margin-top: 20px;">
            <p><strong><?php esc_html_e('Song:'); ?></strong> <?php echo esc_html($details['song_title'] ?? ''); ?> - <?php echo esc_html($details['song_artist'] ?? ''); ?><br>
                <strong><?php esc_html_e('Choreographer:'); ?></strong> <?php echo esc_html($details['choreographer'] ?? '—'); ?><br>
                <strong><?php esc_html_e('Difficulty:'); ?></strong> <?php echo !empty($difficulty_terms) ? esc_html($difficulty_terms[0]->name) : '—'; ?>
            </p>
            
            <p><strong><?php esc_html_e('Counts:'); ?></strong> <?php echo esc_html($details['counts'] ?? '—'); ?> &nbsp;|&nbsp;
                <strong><?php esc_html_e('Walls:'); ?></strong> <?php echo esc_html($details['walls'] ?? '—'); ?> &nbsp;|&nbsp;
                <strong><?php esc_html_e('Restarts:'); ?></strong> <?php echo esc_html($details['restarts'] ?? '—'); ?> &nbsp;|&nbsp;
                <strong><?php esc_html_e('Tags:', 'dancestudio-app'); ?></strong> <?php echo esc_html($details['tags'] ?? '—'); ?>
            </p>
            
            <?php if (!empty($details['sequence'])): ?><p><strong><?php esc_html_e('Sequence:'); ?></strong><br><?php echo nl2br(esc_html($details['sequence'])); ?></p><?php endif; ?>
            
            <?php
            $assigned_group_ids = get_post_meta($choreography_id, '_dsa_assigned_group_ids', true);
            if (!empty($assigned_group_ids) && is_array($assigned_group_ids)) {
                $group_names = array_filter(array_map('get_the_title', $assigned_group_ids));
                if (!empty($group_names)) {
                    echo '<p><strong>' . esc_html__('Assigned to Group(s):', 'dancestudio-app') . '</strong> ' . esc_html(implode(', ', $group_names)) . '</p>';
                }
            }
            ?>
            
            <?php if (!empty($details['song_file_url'])): ?><h3 style="margin-top:30px;"><?php esc_html_e('Song', 'dancestudio-app'); ?></h3><audio controls src="<?php echo esc_url($details['song_file_url']); ?>" style="width: 100%; max-width: 400px;"></audio><?php endif; ?>
            
            <?php if (!empty($details['video_url'])): ?><h3 style="margin-top:30px;"><?php esc_html_e('Steps Video', 'dancestudio-app'); ?></h3><?php echo wp_oembed_get(esc_url($details['video_url'])); ?><?php endif; ?>
            
            <h3 style="margin-top:30px;"><?php esc_html_e('Step Sheet', 'dancestudio-app'); ?></h3>
            <div class="choreography-content" style="border: 1px solid #ddd; padding: 15px; background: #f9f9f9;"><?php echo wp_kses_post(apply_filters('the_content', $post->post_content)); ?></div>
        </div>
        <?php
    }
}