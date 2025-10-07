<?php
/**
 * Student Dashboard -> Profile Tab
 * UPDATED: Reorganized into a modern, responsive three-column layout.
 *
 * @package DanceStudioApp
 */

if ( ! defined('WPINC') ) {
    die;
}

$current_user_id = get_current_user_id();
$user_data = get_userdata($current_user_id);

// Get all user meta in one go for efficiency
$meta = get_user_meta($current_user_id);
$phone = $meta['_dsa_user_phone'][0] ?? '';
$birthday = $meta['_dsa_user_birth_date'][0] ?? '';
$age = $birthday ? dsa_calculate_age($birthday) : '—';
$partner_id = $meta['dsa_partner_user_id'][0] ?? false;

$settings_page_url = add_query_arg('tab', 'settings', get_permalink());

// Get the groups the student is actively enrolled in.
$enrolled_records = get_posts([
    'post_type'      => 'dsa_enroll_record',
    'author'         => $current_user_id,
    'post_status'    => 'publish',
    'posts_per_page' => -1,
]);
$enrolled_group_ids = wp_list_pluck($enrolled_records, 'post_parent');

// Get all public groups
$all_groups_query = new WP_Query([
    'post_type' => 'dsa_group',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC'
]);
?>
<div class="dsa-profile-page">
    <div class="dsa-profile-header">
        <div class="dsa-profile-avatar">
            <?php echo get_avatar($current_user_id, 96); ?>
        </div>
        <div class="dsa-profile-header-info">
            <h2><?php echo esc_html($user_data->display_name);?></h2>
            <p><?php esc_html_e('Welcome to your dashboard!', 'dancestudio-app'); ?></p>
        </div>
        <div class="dsa-profile-header-actions">
             <a href="<?php echo esc_url($settings_page_url);?>" class="dsa-button"><?php esc_html_e('Edit Profile','dancestudio-app');?></a>
        </div>
    </div>

    <div class="dsa-profile-columns-3">
        
        <div class="dsa-profile-col-spacer"></div>

        <div class="dsa-profile-main">
            <div class="dsa-card">
                <h3 class="dsa-card-header"><?php esc_html_e('Personal Information','dancestudio-app');?></h3>
                <div class="dsa-card-body">
                    <p><strong><?php esc_html_e('First Name:','dancestudio-app');?></strong> <span><?php echo esc_html($user_data->first_name);?></span></p>
                    <p><strong><?php esc_html_e('Last Name:','dancestudio-app');?></strong> <span><?php echo esc_html($user_data->last_name);?></span></p>
                    <p><strong><?php esc_html_e('Email:','dancestudio-app');?></strong> <span><?php echo esc_html($user_data->user_email);?></span></p>
                    <p><strong><?php esc_html_e('Phone:','dancestudio-app');?></strong> <span><?php echo esc_html($phone ?: __('Not provided','dancestudio-app'));?></span></p>
                    <p><strong><?php esc_html_e('Birthday:','dancestudio-app');?></strong> <span><?php echo $birthday ? esc_html(date_i18n(get_option('date_format'), strtotime($birthday))) : __('Not provided','dancestudio-app');?></span></p>
                    <p><strong><?php esc_html_e('Age:','dancestudio-app');?></strong> <span><?php echo esc_html($age); ?></span></p>
                </div>
            </div>
        </div>

        <div class="dsa-profile-sidebar">
             <div class="dsa-card">
                <h3 class="dsa-card-header"><?php esc_html_e('Your Groups','dancestudio-app');?></h3>
                <div class="dsa-card-body">
                    <?php if (!empty($enrolled_group_ids)) : ?>
                        <ul class="dsa-group-list">
                            <?php foreach ($enrolled_group_ids as $group_id) : ?>
                                <li><?php echo esc_html(get_the_title($group_id)); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p><?php esc_html_e('You are not currently enrolled in any groups.', 'dancestudio-app'); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="dsa-card">
                <h3 class="dsa-card-header"><?php esc_html_e('Available Groups','dancestudio-app');?></h3>
                <div class="dsa-card-body">
                    <?php if ( $all_groups_query->have_posts() ) : ?>
                        <ul class="dsa-group-list dsa-group-list-all">
                            <?php while($all_groups_query->have_posts()): $all_groups_query->the_post(); ?>
                                <li><?php the_title(); ?></li>
                            <?php endwhile; wp_reset_postdata(); ?>
                        </ul>
                    <?php else: ?>
                        <p><?php esc_html_e('There are no other groups available at this time.', 'dancestudio-app'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>