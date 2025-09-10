<?php
/**
 * View: All Group Classes List Page
 * Adds sortable columns for Title and Date.
 *
 * @package DanceStudioApp
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

if ( ! function_exists( 'dsa_render_all_classes_page' ) ) {
    function dsa_render_all_classes_page() {
        // --- Logic for Sorting ---
        $orderby = isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'class_date';
        $order   = ( isset( $_GET['order'] ) && in_array( strtolower( $_GET['order'] ), ['asc', 'desc'] ) ) ? strtoupper( $_GET['order'] ) : 'DESC';

        $query_args = array(
            'post_type'      => 'dsa_group_class',
            'posts_per_page' => 20,
            'paged'          => get_query_var('paged') ? get_query_var('paged') : 1,
            'order'          => $order,
        );

        if ( 'title' === $orderby ) {
            $query_args['orderby'] = 'title';
        } else { // Default to date sorting
            $query_args['orderby'] = 'meta_value';
            $query_args['meta_key'] = '_dsa_class_date';
        }
        
        ?>
        <div class="wrap">
            <h1>
                <?php esc_html_e( 'All Group Classes', 'dancestudio-app' ); ?>
                <button type="button" id="dsa-add-class-modal-button" class="page-title-action"><?php esc_html_e( 'Log Group Class', 'dancestudio-app' ); ?></button>
            </h1>
            <div id="dsa-admin-notices"></div>

            <table class="wp-list-table widefat fixed striped posts">
                <thead>
                    <tr>
                        <?php 
                        // CORRECTED: Calling the new helper function
                        dsa_render_sortable_table_header( __('Class Title', 'dancestudio-app'), 'title', $orderby, $order ); 
                        dsa_render_sortable_table_header( __('Date', 'dancestudio-app'), 'class_date', $orderby, $order );
                        ?>
                        <th scope="col"><?php esc_html_e('Time', 'dancestudio-app'); ?></th>
                        <th scope="col"><?php esc_html_e('Group', 'dancestudio-app'); ?></th>
                        <th scope="col"><?php esc_html_e('Dance Style', 'dancestudio-app'); ?></th>
                    </tr>
                </thead>
                <tbody id="the-list">
                    <?php
                    $classes_query = new WP_Query($query_args);
                    if ( $classes_query->have_posts() ) :
                        while ( $classes_query->have_posts() ) : $classes_query->the_post();
                            $class_id = get_the_ID();
                            $class_date = get_post_meta( $class_id, '_dsa_class_date', true );
                            $start_time = get_post_meta( $class_id, '_dsa_class_start_time', true );
                            $end_time = get_post_meta( $class_id, '_dsa_class_end_time', true );
                            $group_id = get_post_meta( $class_id, '_dsa_class_group_id', true );
                            $dance_style = get_post_meta( $class_id, '_dsa_class_dance_style', true );
                            $group_name = $group_id ? get_the_title( $group_id ) : __('N/A', 'dancestudio-app');
                            $time_display = $start_time ? date_i18n(get_option('time_format'), strtotime($start_time)) : '';
                            if ($end_time) {
                                $time_display .= ' - ' . date_i18n(get_option('time_format'), strtotime($end_time));
                            }
                            ?>
                            <tr id="post-<?php echo esc_attr($class_id); ?>">
                                <td class="title column-title has-row-actions column-primary" data-colname="<?php esc_attr_e('Class Title','dancestudio-app'); ?>">
                                    <strong><a class="row-title" href="<?php echo esc_url(get_edit_post_link($class_id)); ?>"><?php the_title(); ?></a></strong>
                                    <div class="row-actions"><span class="edit"><a href="<?php echo esc_url(get_edit_post_link($class_id)); ?>"><?php esc_html_e('Edit & Attendance','dancestudio-app');?></a> | </span><span class="trash"><a href="<?php echo esc_url(get_delete_post_link($class_id, '', true)); ?>" class="submitdelete" onclick="return confirm('<?php echo esc_js(__('Are you sure you want to delete this class?','dancestudio-app'));?>');"><?php esc_html_e('Delete Permanently','dancestudio-app');?></a></span></div>
                                </td>
                                <td data-colname="<?php esc_attr_e('Date','dancestudio-app');?>"><?php echo $class_date ? esc_html(date_i18n(get_option('date_format'), strtotime($class_date))) : ''; ?></td>
                                <td data-colname="<?php esc_attr_e('Time','dancestudio-app');?>"><?php echo esc_html($time_display); ?></td>
                                <td data-colname="<?php esc_attr_e('Group','dancestudio-app');?>"><?php echo esc_html($group_name); ?></td>
                                <td data-colname="<?php esc_attr_e('Dance Style','dancestudio-app');?>"><?php echo esc_html($dance_style ?: 'N/A'); ?></td>
                            </tr>
                            <?php
                        endwhile;
                        wp_reset_postdata();
                    else: ?>
                        <tr class="no-items"><td class="colspanchange" colspan="5"><?php esc_html_e('No group classes logged yet.', 'dancestudio-app'); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div id="dsa-add-class-modal" title="<?php esc_attr_e('Log New Group Class', 'dancestudio-app'); ?>" style="display:none;">
                <form id="dsa-add-class-form-modal" method="post">
                    <div id="dsa-modal-messages" class="notice" style="display:none; margin:0 0 10px; padding:10px;"></div>
                    <table class="form-table">
                        <tr valign="top"><th scope="row"><label for="dsa_class_label_modal"><?php _e('Class Label/Title:','dancestudio-app'); ?></label></th><td><input type="text" id="dsa_class_label_modal" name="dsa_class_label" class="regular-text" required/></td></tr>
                        <tr valign="top"><th scope="row"><label for="dsa_class_date_modal"><?php _e('Date:','dancestudio-app'); ?></label></th><td><input type="date" id="dsa_class_date_modal" name="dsa_class_date" required/></td></tr>
                        <tr valign="top"><th scope="row"><label for="dsa_class_start_time_modal"><?php _e('Start Time:','dancestudio-app'); ?></label></th><td><input type="time" id="dsa_class_start_time_modal" name="dsa_class_start_time" required/></td></tr>
                        <tr valign="top"><th scope="row"><label for="dsa_class_end_time_modal"><?php _e('End Time:','dancestudio-app'); ?></label></th><td><input type="time" id="dsa_class_end_time_modal" name="dsa_class_end_time" /></td></tr>
                        
                        <tr valign="top"><th scope="row"><label for="dsa_class_group_id_modal"><?php _e('Group:','dancestudio-app'); ?></label></th>
                            <td>
                                <?php
                                $all_groups = get_posts(array('post_type' => 'dsa_group', 'numberposts' => -1, 'orderby' => 'post_title', 'order' => 'ASC'));
                                if ( ! empty($all_groups) ) : ?>
                                    <select name="dsa_class_group_id" id="dsa_class_group_id_modal" class="regular-text">
                                        <option value="0"><?php esc_html_e('-- Select Group --','dancestudio-app'); ?></option>
                                        <?php foreach ( $all_groups as $group_post ) : ?>
                                            <option value="<?php echo esc_attr( $group_post->ID ); ?>">
                                                <?php echo esc_html( $group_post->post_title ); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else : ?>
                                    <em><?php esc_html_e('No dance groups have been created yet.', 'dancestudio-app'); ?></em>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr valign="top"><th scope="row"><label for="dsa_class_dance_style_modal"><?php _e('Dance Style:','dancestudio-app'); ?></label></th><td><input type="text" id="dsa_class_dance_style_modal" name="dsa_class_dance_style" class="regular-text"/></td></tr>
                        <tr valign="top"><th scope="row"><label for="dsa_class_notes_modal"><?php _e('Notes/Content:','dancestudio-app'); ?></label></th><td><textarea id="dsa_class_notes_modal" name="dsa_class_notes" rows="4" class="large-text"></textarea></td></tr>
                    </table>
                </form>
            </div>
        </div>
        <script type="text/javascript">
            jQuery(document).ready(function($){if(typeof $.fn.dialog!=='undefined'){var $dialog=$('#dsa-add-class-modal').dialog({autoOpen:false,modal:true,width:550,resizable:false,buttons:[{text:"<?php echo esc_js(__('Save Class','dancestudio-app'));?>",click:function(){$('#dsa-add-class-form-modal').trigger('submit');}},{text:"<?php echo esc_js(__('Cancel','dancestudio-app'));?>",click:function(){$(this).dialog('close');}}],close:function(){$('#dsa-add-class-form-modal')[0].reset();$('#dsa-modal-messages').html('').hide();}});$('#dsa-add-class-modal-button').on('click',function(){$dialog.dialog('open');});$('#dsa-add-class-form-modal').on('submit',function(e){e.preventDefault();var $form=$(this);var $messagesDiv=$('#dsa-modal-messages');var $submitBtn=$dialog.parent().find('.ui-dialog-buttonpane button:first');$submitBtn.button('disable').text('<?php echo esc_js(__('Saving...','dancestudio-app'));?>');$messagesDiv.html('').removeClass('notice-error').hide();var formData=$form.serializeArray();formData.push({name:'action',value:'dsa_add_class_session_ajax'});formData.push({name:'nonce',value:'<?php echo wp_create_nonce("dsa_add_class_action_ajax");?>'});$.post(ajaxurl,$.param(formData),function(response){if(response.success){$('#dsa-admin-notices').html('<div class="notice notice-success is-dismissible"><p>'+response.data.message+'</p></div>').show().delay(4000).slideUp();if($('.no-items').length){location.reload();}else{$('#the-list').prepend(response.data.new_row_html);}$dialog.dialog('close');}else{$messagesDiv.html('<p>'+response.data.message+'</p>').addClass('notice notice-error').show();}}).fail(function(){$messagesDiv.html('<p>An unexpected error occurred. Please try again.</p>').addClass('notice notice-error').show();}).always(function(){$submitBtn.button('enable').text('<?php echo esc_js(__('Save Class','dancestudio-app'));?>');});});}else{console.error("DSA: jQuery UI Dialog library not loaded.");}});
        </script>
        <?php
    }
}
?>