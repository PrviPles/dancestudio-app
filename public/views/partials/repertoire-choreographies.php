<?php
/**
 * Partial: Displays Choreographies for the student's groups.
 * UPDATED: Replaced card view with a full-featured, filterable, and sortable table.
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
]);
$enrolled_group_ids = wp_list_pluck($enrollment_records, 'post_parent');

if (empty($enrolled_group_ids)) {
    echo '<p>' . esc_html__('You are not enrolled in any groups, so no choreographies can be displayed.', 'dancestudio-app') . '</p>';
    return;
}

// --- Get filter and sorting parameters from the URL ---
$orderby           = isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'title';
$order             = isset( $_GET['order'] ) && strtolower($_GET['order']) === 'desc' ? 'DESC' : 'ASC';
$difficulty_filter = isset( $_GET['difficulty_filter'] ) ? absint( $_GET['difficulty_filter'] ) : 0;
$paged             = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;

$query_args = [
    'post_type'      => 'dsa_choreography',
    'posts_per_page' => 10, // Items per page
    'orderby'        => $orderby,
    'order'          => $order,
    'paged'          => $paged,
    'meta_query' => [
        'relation' => 'OR',
    ]
];

// Add a meta query for each of the student's enrolled groups
foreach ($enrolled_group_ids as $group_id) {
    $query_args['meta_query'][] = [
        'key'     => '_dsa_assigned_group_ids',
        'value'   => '"' . $group_id . '"',
        'compare' => 'LIKE',
    ];
}

// Add taxonomy query if a difficulty is selected
if ( $difficulty_filter > 0 ) {
    $query_args['tax_query'][] = [
        'taxonomy' => 'dsa_difficulty_level',
        'field'    => 'term_id',
        'terms'    => $difficulty_filter,
    ];
}

$choreographies_query = new WP_Query($query_args);
?>
<form method="get">
    <input type="hidden" name="tab" value="repertoire">
    <input type="hidden" name="sub_tab" value="choreographies">

    <div class="dsa-filters-wrapper">
        <div class="dsa-filter-item">
            <label for="difficulty_filter"><?php esc_html_e('Filter by Difficulty:', 'dancestudio-app'); ?></label>
            <?php
            wp_dropdown_categories([
                'show_option_all' => __('All Difficulties', 'dancestudio-app'),
                'taxonomy'        => 'dsa_difficulty_level',
                'name'            => 'difficulty_filter',
                'selected'        => $difficulty_filter,
                'hierarchical'    => true,
                'hide_empty'      => true,
                'value_field'     => 'term_id',
            ]);
            ?>
        </div>
        <div class="dsa-filter-item">
            <input type="submit" class="dsa-button" value="<?php esc_attr_e('Filter', 'dancestudio-app'); ?>">
        </div>
    </div>

    <table class="dsa-front-end-table">
        <thead>
            <tr>
                <th><?php esc_html_e('Name', 'dancestudio-app'); ?></th>
                <th><?php esc_html_e('Song', 'dancestudio-app'); ?></th>
                <th><?php esc_html_e('Difficulty', 'dancestudio-app'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php if ( $choreographies_query->have_posts() ) :
            while ( $choreographies_query->have_posts() ) : $choreographies_query->the_post();
                $choreography_id = get_the_ID();
                $details = get_post_meta($choreography_id, '_dsa_choreography_details', true);
                $song_display = !empty($details['song_title']) ? $details['song_title'] : '—';
                $difficulty_terms = get_the_terms($choreography_id, 'dsa_difficulty_level');
                $difficulty_display = !empty($difficulty_terms) ? esc_html($difficulty_terms[0]->name) : '—';
            ?>
            <tr>
                <td data-label="<?php esc_attr_e('Name', 'dancestudio-app'); ?>"><strong><?php the_title(); ?></strong></td>
                <td data-label="<?php esc_attr_e('Song', 'dancestudio-app'); ?>"><?php echo esc_html($song_display); ?></td>
                <td data-label="<?php esc_attr_e('Difficulty', 'dancestudio-app'); ?>"><?php echo $difficulty_display; ?></td>
            </tr>
            <?php endwhile; else: ?>
            <tr class="no-items"><td colspan="3"><?php esc_html_e('No choreographies found matching your criteria.', 'dancestudio-app'); ?></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</form>

<?php
// Pagination
$total_pages = $choreographies_query->max_num_pages;
if ($total_pages > 1) {
    echo '<div class="dsa-pagination">';
    echo paginate_links([
        'base'      => add_query_arg('paged', '%#%'),
        'format'    => '',
        'prev_text' => __('&laquo;'),
        'next_text' => __('&raquo;'),
        'total'     => $total_pages,
        'current'   => $paged,
    ]);
    echo '</div>';
}
wp_reset_postdata();
?>