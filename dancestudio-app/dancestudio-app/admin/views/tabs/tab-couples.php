<?php
/**
 * View Part: Renders the "Couples" tab content with a searchable table.
 * @package DanceStudioApp
 */
if(!defined('WPINC')){die;}

if(!function_exists('dsa_render_couples_tab')){
    function dsa_render_couples_tab(){
        if(isset($_GET['message']) && $_GET['message'] === 'unpaired') {
            echo '<div id="message" class="updated notice is-dismissible"><p>' . esc_html__('Couple has been unpaired.', 'dancestudio-app') . '</p></div>';
        }
        
        $search_term = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
        ?>
        <div class="wrap">
            <div id="couples-tab-content">
                <div class="dsa-card" style="padding: 15px; margin-bottom: 30px;">
                    <h2><span class="dashicons dashicons-admin-users" style="vertical-align: middle;"></span> <?php _e('Create/Update Couple','dancestudio-app');?></h2>
                    <div id="dsa-pairing-messages" class="notice" style="display:none; margin-bottom: 15px;"></div>
                    <form id="dsa-couple-pairing-form" style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 15px; align-items: end;">
                        <?php wp_nonce_field( 'dsa_pairing_action_ajax', 'dsa_pairing_nonce' ); ?>
                        <div>
                            <label for="s1id" style="display:block; margin-bottom:5px;"><?php _e('Student 1:','dancestudio-app');?></label>
                            <?php wp_dropdown_users(['name'=>'dsa_student1_id','id'=>'s1id','role__in'=>['student','subscriber'],'show_option_none'=>'-- Select --', 'class' => 'widefat']);?>
                        </div>
                        <div>
                            <label for="s2id" style="display:block; margin-bottom:5px;"><?php _e('Student 2:','dancestudio-app');?></label>
                            <?php wp_dropdown_users(['name'=>'dsa_student2_id','id'=>'s2id','role__in'=>['student','subscriber'],'show_option_none'=>'-- Select --', 'class' => 'widefat']);?>
                        </div>
                        <div>
                            <input type="submit" name="submit" id="submit" class="button button-primary button-large" value="<?php esc_attr_e('Pair Students');?>">
                        </div>
                    </form>
                </div>
                
                <form method="get">
                    <input type="hidden" name="page" value="dsa-couples-tab">
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
                            <th scope="col"><?php _e('Paired On','dancestudio-app');?></th>
                            <th scope="col"><?php _e('Wedding Date','dancestudio-app');?></th>
                            <th scope="col"><?php _e('Package Progress','dancestudio-app');?></th>
                            <th scope="col"><?php _e('Actions','dancestudio-app');?></th>
                        </tr>
                    </thead>
                    <tbody id="the-list">
                        <?php
                        $matching_couple_member_ids = [];
                        if ( ! empty($search_term) ) {
                            $users_found = get_users([
                                'role__in' => ['student', 'subscriber'],
                                'search' => '*' . esc_attr($search_term) . '*',
                                'search_columns' => ['user_login', 'user_email', 'user_nicename', 'display_name'],
                                'fields' => 'ID'
                            ]);
                            foreach( $users_found as $user_id ) {
                                $matching_couple_member_ids[] = $user_id;
                                $partner_id = get_user_meta($user_id, 'dsa_partner_user_id', true);
                                if ($partner_id) $matching_couple_member_ids[] = absint($partner_id);
                            }
                            $matching_couple_member_ids = array_unique($matching_couple_member_ids);
                        }

                        $paired_user_ids = get_users([
                            'role__in'     => ['student', 'subscriber'],
                            'meta_key'     => 'dsa_partner_user_id',
                            'meta_compare' => 'EXISTS',
                            'fields'       => 'ID',
                            'include'      => !empty($matching_couple_member_ids) ? $matching_couple_member_ids : '',
                        ]);
                        
                        $processed_ids = [];
                        $found_couples = false;
                        if (!empty($paired_user_ids)) {
                            foreach ($paired_user_ids as $u1_id) {
                                if (in_array($u1_id, $processed_ids)) continue;
                                
                                $u2_id = get_user_meta($u1_id, 'dsa_partner_user_id', true);
                                if (empty($u2_id) || !is_numeric($u2_id)) continue;
                                $u2_id = absint($u2_id);
                                $partner_of_partner_id = get_user_meta($u2_id, 'dsa_partner_user_id', true);
                                if ( absint($partner_of_partner_id) != $u1_id ) continue;

                                $processed_ids[] = $u1_id;
                                $processed_ids[] = $u2_id;
                                $found_couples = true;
                                
                                $u1_data = get_userdata($u1_id);
                                $u2_data = get_userdata($u2_id);
                                if(!$u1_data || !$u2_data) continue;

                                $pairing_date = get_user_meta($u1_id, '_dsa_pairing_date', true);
                                $wedding_date = get_user_meta($u1_id, 'dsa_wedding_date', true);
                                $package_progress = function_exists('dsa_get_couple_package_progress') ? dsa_get_couple_package_progress($u1_id, $u2_id) : 'N/A';

                                $details_link = add_query_arg(['page' => 'dsa-couples-tab', 'action' => 'view_couple_details', 'user1_id' => $u1_id, 'user2_id' => $u2_id], admin_url('admin.php'));
                                $unpair_link = add_query_arg(['action' => 'dsa_unpair_couple', 'user1_id' => $u1_id, 'user2_id' => $u2_id, '_wpnonce' => wp_create_nonce('dsa_unpair_nonce')], admin_url('admin-post.php'));
                                ?>
                                <tr>
                                    <td><strong><?php echo esc_html($u1_data->display_name . ' & ' . $u2_data->display_name); ?></strong></td>
                                    <td><?php echo $pairing_date ? esc_html(date_i18n(get_option('date_format'), strtotime($pairing_date))) : '—'; ?></td>
                                    <td><?php echo $wedding_date ? esc_html(date_i18n(get_option('date_format'), strtotime($wedding_date))) : '—'; ?></td>
                                    <td><?php echo esc_html($package_progress); ?></td>
                                    <td>
                                        <a href="<?php echo esc_url($details_link); ?>" class="button button-secondary">Details</a>
                                        <a href="<?php echo esc_url($unpair_link); ?>" class="button button-link-delete" onclick="return confirm('<?php esc_attr_e('Are you sure?','dancestudio-app'); ?>');">Unpair</a>
                                    </td>
                                </tr>
                                <?php
                            }
                        }
                        
                        if (!$found_couples) {
                            $message = !empty($search_term) ? __('No couples found matching your search.', 'dancestudio-app') : __('No couples have been created yet.', 'dancestudio-app');
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