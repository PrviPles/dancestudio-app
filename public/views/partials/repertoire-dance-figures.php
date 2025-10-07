<?php
/**
 * Partial: Displays Dance Figures for the student's groups.
 * @package DanceStudioApp
 */

if ( ! defined('WPINC') ) {
    die;
}

$current_user_id = get_current_user_id();

// Get the groups the student is actively enrolled in.
$enrollment_records = get_posts([
    'post_type'      => 'dsa_enroll_record',
    'author'         => $current_user_id,
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'fields'         => 'id',
]);
$enrolled_group_ids = wp_list_pluck($enrollment_records, 'post_parent');

if (empty($enrolled_group_ids)) {
    echo '<p>' . esc_html__('You are not enrolled in any groups, so no dance figures can be displayed.', 'dancestudio-app') . '</p>';
    return;
}

// Find all unique dance styles ('dsa_dance' taxonomy) associated with the student's groups
$dance_term_ids = [];
foreach($enrolled_group_ids as $group_id) {
    $terms = get_the_terms($group_id, 'dsa_dance');
    if (!empty($terms) && !is_wp_error($terms)) {
        $dance_term_ids = array_merge($dance_term_ids, wp_list_pluck($terms, 'term_id'));
    }
}
$unique_dance_term_ids = array_unique($dance_term_ids);

if (empty($unique_dance_term_ids)) {
    echo '<p>' . esc_html__('Your group(s) are not associated with any specific dance styles.', 'dancestudio-app') . '</p>';
    return;
}

// Query for dance figures that are in any of the relevant dance taxonomies
$figures_query = new WP_Query([
    'post_type' => 'dsa_dance_figure',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC',
    'tax_query' => [
        [
            'taxonomy' => 'dsa_dance',
            'field'    => 'term_id',
            'terms'    => $unique_dance_term_ids,
        ]
    ]
]);

if ( !$figures_query->have_posts() ) {
    echo '<p>' . esc_html__('There are no dance figures listed for the styles taught in your group(s).', 'dancestudio-app') . '</p>';
    return;
}
?>
<table class="dsa-front-end-table">
    <thead>
        <tr>
            <th><?php esc_html_e('Figure Name', 'dancestudio-app'); ?></th>
            <th><?php esc_html_e('Dance', 'dancestudio-app'); ?></th>
            <th><?php esc_html_e('Difficulty', 'dancestudio-app'); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php while($figures_query->have_posts()): $figures_query->the_post(); 
        $dances = get_the_terms(get_the_ID(), 'dsa_dance');
        $difficulty = get_the_terms(get_the_ID(), 'dsa_difficulty_level');
    ?>
        <tr>
            <td data-label="<?php esc_attr_e('Figure Name', 'dancestudio-app'); ?>"><?php the_title(); ?></td>
            <td data-label="<?php esc_attr_e('Dance', 'dancestudio-app'); ?>"><?php echo !empty($dances) ? esc_html($dances[0]->name) : 'N/A'; ?></td>
            <td data-label="<?php esc_attr_e('Difficulty', 'dancestudio-app'); ?>"><?php echo !empty($difficulty) ? esc_html($difficulty[0]->name) : 'N/A'; ?></td>
        </tr>
    <?php endwhile; wp_reset_postdata(); ?>
    </tbody>
</table>