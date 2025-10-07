<?php
/**
 * View Part: Renders the "Couples" tab content with a searchable table.
 * UPDATED: Optimized database queries to fix N+1 problem with a robust method.
 * @package DanceStudioApp
 */
if(!defined('WPINC')){die;}

if(!function_exists('dsa_render_couples_tab')){
    function dsa_render_couples_tab(){
        if(isset($_GET['message']) && $_GET['message'] === 'unpaired') {
            echo '<div id="message" class="updated notice is-dismissible"><p>' . esc_html__('Couple has been unpaired.', 'dancestudio-app') . '</p></div>';
        }
        
        $search_term = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
        $type_filter = isset( $_GET['couple_type_filter'] ) && !empty($_GET['couple_type_filter']) ? sanitize_key($_GET['couple_type_filter']) : '';
        $status_filter = isset( $_GET['couple_status'] ) ? sanitize_key( $_GET['couple_status'] ) : 'active';

        // Get counts for status filters
        $total_couples_query = new WP_User_Query(['role__in' => ['student', 'subscriber'], 'meta_key' => 'dsa_partner_user_id', 'meta_compare' => 'EXISTS', 'fields' => 'ID']);
        $active_couples_query = new WP_User_Query(['role__in' => ['student', 'subscriber'], 'meta_key' => 'dsa_partner_user_id', 'meta_compare' => 'EXISTS', 'fields' => 'ID', 'meta_query' => ['relation' => 'OR', ['key' => '_dsa_couple_status', 'compare' => 'NOT EXISTS'], ['key' => '_dsa_couple_status', 'value' => 'archived', 'compare' => '!=']]]);
        $archived_couples_query = new WP_User_Query(['role__in' => ['student', 'subscriber'], 'meta_key' => 'dsa_partner_user_id', 'meta_compare' => 'EXISTS', 'fields' => 'ID', 'meta_query' => [['key' => '_dsa_couple_status', 'value' => 'archived']]]);
        
        $total_count = floor($total_couples_query->get_total() / 2);
        $active_count = floor($active_couples_query->get_total() / 2);
        $archived_count = floor($archived_couples_query->get_total() / 2);
        ?>
        <div class="wrap">
            <div id="couples-tab-content">
                <div class="dsa-card" style="padding: 15px; margin-bottom: 30px;">
                    <h2><span class="dashicons dashicons-admin-users" style="vertical-align: middle;"></span> <?php _e('Create/Update Couple','dancestudio-app');?></h2>
                    <div id="dsa-pairing-messages" class="notice" style="display:none; margin-bottom: 15px;"></div>
                    <form id="dsa-couple-pairing-form" style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
                        <?php wp_nonce_field( 'dsa_pairing_action_ajax', 'dsa_pairing_nonce' ); ?>
                        <div>
                            <label for="dsa_student1_select" style="display:block; margin-bottom:5px;"><?php _e('Leader:','dancestudio-app');?></label>
                            <select name="dsa_student1_id" id="dsa_student1_select" class="dsa-student-search-select" style="width: 100%;"></select>
                        </div>
                        <div>
                            <label for="dsa_student2_select" style="display:block; margin-bottom:5px;"><?php _e('Follower:','dancestudio-app');?></label>
                            <select name="dsa_student2_id" id="dsa_student2_select" class="dsa-student-search-select" style="width: 100%;"></select>
                        </div>
                        <div>
                            <label for="dsa_couple_type_select" style="display:block; margin-bottom:5px;"><?php _e('Couple Type:','dancestudio-app');?></label>
                            <select name="dsa_couple_type" id="dsa_couple_type_select" style="width: 100%;">
                                <option value="wedding"><?php _e('Wedding', 'dancestudio-app'); ?></option>
                                <option value="recreation"><?php _e('Recreation', 'dancestudio-app'); ?></option>
                                <option value="competitive"><?php _e('Competitive', 'dancestudio-app'); ?></option>
                            </select>
                        </div>
                        <div>
                            <input type="submit" name="submit" id="submit" class="button button-primary button-large" value="<?php esc_attr_e('Pair Students');?>">
                        </div>
                    </form>
                </div>
                
                <ul class="subsubsub">
                    <li><a href="?page=dsa-couples-tab" class="<?php if(empty($status_filter)) echo 'current'; ?>">All <span class="count">(<?php echo $total_count; ?>)</span></a> |</li>
                    <li><a href="?page=dsa-couples-tab&couple_status=active" class="<?php if($status_filter === 'active') echo 'current'; ?>">Active <span class="count">(<?php echo $active_count; ?>)</span></a> |</li>
                    <li><a href="?page=dsa-couples-tab&couple_status=archived" class="<?php if($status_filter === 'archived') echo 'current'; ?>">Archived <span class="count">(<?php echo $archived_count; ?>)</span></a></li>
                </ul>

                <form method="get">
                    <input type="hidden" name="page" value="dsa-couples-tab">
                    <input type="hidden" name="couple_status" value="<?php echo esc_attr($status_filter); ?>">
                    <div class="tablenav top">
                        <div class="alignleft actions">
                            <select name="couple_type_filter" id="couple_type_filter">
                                <option value=""><?php _e('All Types', 'dancestudio-app'); ?></option>
                                <option value="wedding" <?php selected($type_filter, 'wedding'); ?>><?php _e('Wedding', 'dancestudio-app'); ?></option>
                                <option value="recreation" <?php selected($type_filter, 'recreation'); ?>><?php _e('Recreation', 'dancestudio-app'); ?></option>
                                <option value="competitive" <?php selected($type_filter, 'competitive'); ?>><?php _e('Competitive', 'dancestudio-app'); ?></option>
                            </select>
                            <input type="submit" class="button" value="<?php esc_attr_e('Filter'); ?>">
                        </div>
                    </div>
                    <p class="search-box">
                        <label class="screen-reader-text" for="dsa-couple-search-input"><?php _e('Search Couples:','dancestudio-app');?></label>
                        <input type="search" id="dsa-couple-search-input" name="s" value="<?php echo esc_attr($search_term); ?>">
                        <input type="submit" id="search-submit" class="button" value="<?php esc_attr_e('Search Couples','dancestudio-app');?>">
                    </p>
                </form>

                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col"><?php _e('Couple','dancestudio-app');?></th>
                            <th scope="col"><?php _e('Couple Type','dancestudio-app');?></th>
                            <th scope="col"><?php _e('Paired On','dancestudio-app');?></th>
                            <th scope="col"><?php _e('Wedding Date','dancestudio-app');?></th>
                            <th scope="col"><?php _e('Actions','dancestudio-app');?></th>
                        </tr>
                    </thead>
                    <tbody id="the-list">
                        <?php
                        // --- PERFORMANCE UPGRADE (BUG FIX) START ---
                        $query_args = [
                            'role__in'     => ['student', 'subscriber'],
                            'meta_key'     => 'dsa_partner_user_id',
                            'meta_compare' => 'EXISTS',
                            'fields'       => 'ID',
                        ];

                        $meta_query = ['relation' => 'AND'];
                        if ($status_filter === 'active') {
                            $meta_query[] = [
                                'relation' => 'OR',
                                ['key' => '_dsa_couple_status', 'compare' => 'NOT EXISTS'],
                                ['key' => '_dsa_couple_status', 'value' => 'archived', 'compare' => '!=']
                            ];
                        } elseif ($status_filter === 'archived') {
                             $meta_query[] = ['key' => '_dsa_couple_status', 'value' => 'archived'];
                        }

                        if ( ! empty($type_filter) ) {
                            $meta_query[] = [
                                'key' => '_dsa_couple_type',
                                'value' => $type_filter,
                            ];
                        }
                        if (count($meta_query) > 1) {
                            $query_args['meta_query'] = $meta_query;
                        }

                        if ( ! empty($search_term) ) {
                            $users_found = get_users(['role__in' => ['student', 'subscriber'], 'search' => '*' . esc_attr($search_term) . '*', 'search_columns' => ['user_login', 'user_email', 'display_name'], 'fields' => 'ID']);
                            if (!empty($users_found)) {
                                $query_args['include'] = $users_found;
                            } else {
                                $query_args['include'] = [0]; 
                            }
                        }
                        
                        // 1. Get the initial list of user IDs that match the filters.
                        $paired_user_ids = get_users($query_args);
                        
                        $processed_ids = [];
                        $all_couple_user_ids = [];
                        $couples_to_render = [];
                        $found_couples = false;

                        if (!empty($paired_user_ids)) {
                            // 2. Pre-cache the 'dsa_partner_user_id' meta for all matched users in one query.
                            update_meta_cache('user', $paired_user_ids);

                            foreach ($paired_user_ids as $u1_id) {
                                if (in_array($u1_id, $processed_ids)) continue;
                                
                                $u2_id = get_user_meta($u1_id, 'dsa_partner_user_id', true);
                                if (empty($u2_id) || !is_numeric($u2_id)) continue;
                                
                                $u2_partner_check = get_user_meta($u2_id, 'dsa_partner_user_id', true);
                                if ( absint($u2_partner_check) != $u1_id ) continue;

                                // We have a valid pair. Add them to our lists.
                                $processed_ids[] = $u1_id;
                                $processed_ids[] = $u2_id;
                                $all_couple_user_ids[] = $u1_id;
                                $all_couple_user_ids[] = $u2_id;
                                $couples_to_render[] = ['u1' => $u1_id, 'u2' => $u2_id];
                                $found_couples = true;
                            }

                            // 3. Now, pre-cache all user data and all other meta for all users we need to display.
                            if ($found_couples) {
                                update_meta_cache('user', $all_couple_user_ids);
                                // The get_userdata calls below will now be fast as they will hit the cache.
                            }
                        }
                        
                        // 4. Loop through our final list of couples and display them. All data is cached.
                        if ($found_couples) {
                            foreach ($couples_to_render as $couple) {
                                $u1_id = $couple['u1'];
                                $u2_id = $couple['u2'];
                                $u1_data = get_userdata($u1_id);
                                $u2_data = get_userdata($u2_id);
                                if(!$u1_data || !$u2_data) continue;

                                // These calls are now fast because of update_meta_cache()
                                $couple_type  = get_user_meta($u1_id, '_dsa_couple_type', true) ?: 'N/A';
                                $pairing_date = get_user_meta($u1_id, '_dsa_pairing_date', true);
                                $wedding_date = get_user_meta($u1_id, 'dsa_wedding_date', true);

                                $details_link = add_query_arg(['page' => 'dsa-couples-tab', 'action' => 'view_couple_details', 'user1_id' => $u1_id, 'user2_id' => $u2_id], admin_url('admin.php'));
                                $unpair_link = add_query_arg(['action' => 'dsa_unpair_couple', 'user1_id' => $u1_id, 'user2_id' => $u2_id, '_wpnonce' => wp_create_nonce('dsa_unpair_nonce')], admin_url('admin-post.php'));
                                ?>
                                <tr>
                                    <td><strong><?php echo esc_html($u1_data->display_name . ' & ' . $u2_data->display_name); ?></strong></td>
                                    <td><?php echo esc_html(ucfirst($couple_type)); ?></td>
                                    <td><?php echo $pairing_date ? esc_html(date_i18n(get_option('date_format'), strtotime($pairing_date))) : '—'; ?></td>
                                    <td><?php echo $wedding_date ? esc_html(date_i18n(get_option('date_format'), strtotime($wedding_date))) : '—'; ?></td>
                                    <td>
                                        <a href="<?php echo esc_url($details_link); ?>" class="button button-secondary">Details</a>
                                        <a href="<?php echo esc_url($unpair_link); ?>" class="button button-link-delete" onclick="return confirm('<?php esc_attr_e('Are you sure?','dancestudio-app'); ?>');">Unpair</a>
                                    </td>
                                </tr>
                                <?php
                            }
                        }
                        // --- PERFORMANCE UPGRADE (BUG FIX) END ---
                        
                        if (!$found_couples) {
                            $message = (!empty($search_term) || !empty($type_filter)) ? __('No couples found matching your criteria.', 'dancestudio-app') : __('No couples have been created yet.', 'dancestudio-app');
                            echo '<tr><td colspan="5">' . $message . '</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
                <script type="text/javascript">
                    jQuery(document).ready(function($){'use strict';
                        $('#dsa-couple-pairing-form').on('submit',function(e){
                            e.preventDefault();
                            var $form=$(this); var $submitButton=$form.find('input[type="submit"]'); var $messagesDiv=$('#dsa-pairing-messages');
                            $submitButton.val('<?php echo esc_js(__('Pairing...','dancestudio-app'));?>').prop('disabled',true);
                            $messagesDiv.html('').removeClass('notice-success notice-error').slideUp();
                            var formData=$form.serializeArray();
                            formData.push({name:'action',value:'dsa_pair_couple'});
                            formData.push({name:'nonce', value: $('#dsa_pairing_nonce').val() });
                            $.post(ajaxurl,$.param(formData),function(response){
                                if(response.success){
                                    $messagesDiv.html('<p>'+response.data.message+'</p>').addClass('notice-success').slideDown();
                                    setTimeout(function(){location.href = location.pathname + '?page=dsa-couples-tab';},1200);
                                }else{
                                    $messagesDiv.html('<p>'+response.data.message+'</p>').addClass('notice-error').slideDown();
                                    $submitButton.val('<?php echo esc_js(__("Pair Students","dancestudio-app"));?>').prop('disabled',false);
                                }
                            }).fail(function(){
                                $messagesDiv.html('<p>An unexpected error occurred.</p>').addClass('notice-error').show();
                                $submitButton.val('<?php echo esc_js(__("Pair Students","dancestudio-app"));?>').prop('disabled',false);
                            });
                        });
                    });
                </script>
            </div>
        </div>
        <?php
    }
}
?>