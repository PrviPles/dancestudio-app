<?php
/**
 * View: Renders the main Statistics page.
 */
if ( ! defined( 'WPINC' ) ) die;

function dsa_render_statistics_page() {
    $selected_year = isset($_GET['filter_year']) ? absint($_GET['filter_year']) : date('Y');
    $selected_group = isset($_GET['filter_group']) ? absint($_GET['filter_group']) : 0;
    $selected_teachers = isset($_GET['filter_teachers']) && is_array($_GET['filter_teachers']) ? array_map('absint', $_GET['filter_teachers']) : [];

    $stats_data = function_exists('dsa_get_studio_statistics_data') ? dsa_get_studio_statistics_data([
        'year' => $selected_year,
        'group_id' => $selected_group,
        'teacher_ids' => $selected_teachers,
    ]) : [];
    ?>
    <style>
        .dsa-stat-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; margin-top: 10px; }
        .dsa-stat-card { background: #fff; padding: 20px; border-left: 5px solid #3498db; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .dsa-stat-card h3 { margin: 0; font-size: 16px; color: #777; }
        .dsa-stat-card .stat-number { font-size: 36px; font-weight: bold; color: #2c3e50; margin-top: 5px; }
        .dsa-filters-wrapper { margin-top: 20px; background: #fff; padding: 20px; border: 1px solid #ccd0d4; }
        .dsa-filters-main { display: flex; flex-wrap: wrap; gap: 20px; align-items: center; }
        .dsa-teacher-filters { margin-top: 15px; border-top: 1px solid #eee; padding-top: 15px; }
        .dsa-teacher-filters label { margin-right: 15px; display: inline-block; }
    </style>
    <div class="wrap dsa-statistics-page">
        <h1 class="wp-heading-inline"><?php esc_html_e( 'Studio Statistics', 'dancestudio-app' ); ?></h1>
        <hr class="wp-header-end">

        <form method="get" class="dsa-filters-wrapper">
            <input type="hidden" name="page" value="dsa-statistics">
            <div class="dsa-filters-main">
                <div>
                    <label for="filter_year"><strong><?php _e('Year:','dancestudio-app');?></strong></label><br>
                    <select name="filter_year" id="filter_year">
                        <?php for ($y = date('Y') + 2; $y >= 2020; $y--): ?>
                            <option value="<?php echo $y; ?>" <?php selected($selected_year, $y); ?>><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div>
                    <label for="filter_group"><strong><?php _e('Group:','dancestudio-app');?></strong></label><br>
                    <select name="filter_group" id="filter_group">
                        <option value="0"><?php esc_html_e( 'All Groups', 'dancestudio-app' ); ?></option>
                        <?php
                        $groups = get_posts(['post_type' => 'dsa_group', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC']);
                        if ( $groups ) {
                            foreach ( $groups as $group ) {
                                echo '<option value="' . esc_attr( $group->ID ) . '" ' . selected( $selected_group, $group->ID, false ) . '>' . esc_html( $group->post_title ) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>
            
            <div class="dsa-teacher-filters">
                <strong><?php _e('Teachers:','dancestudio-app');?></strong><br>
                <?php
                $teachers = get_users(['role__in' => ['teacher', 'studio_manager', 'administrator'], 'orderby' => 'display_name']);
                foreach ($teachers as $teacher) {
                    echo '<label><input type="checkbox" name="filter_teachers[]" value="' . esc_attr($teacher->ID) . '" ' . checked(in_array($teacher->ID, $selected_teachers), true, false) . '> ' . esc_html($teacher->display_name) . '</label>';
                }
                ?>
            </div>
            
            <p style="margin-top: 20px;"><input type="submit" class="button button-primary" value="<?php _e('Filter Statistics', 'dancestudio-app');?>"></p>
        </form>

        <div id="dsa-key-stats" class="dsa-stat-grid">
            <div class="dsa-stat-card"><h3>Total Lessons Held</h3><div class="stat-number"><?php echo esc_html($stats_data['total_lessons'] ?? 0); ?></div></div>
            <div class="dsa-stat-card"><h3>Unique Students Attended</h3><div class="stat-number"><?php echo esc_html($stats_data['unique_students'] ?? 0); ?></div></div>
            <div class="dsa-stat-card"><h3>Total Student-Hours</h3><div class="stat-number"><?php echo esc_html($stats_data['student_hours'] ?? 0); ?></div></div>
            <div class="dsa-stat-card"><h3>Busiest Day</h3><div class="stat-number"><?php echo esc_html($stats_data['busiest_day'] ?? '-'); ?></div></div>
            <div class="dsa-stat-card"><h3>Most Popular Group</h3><div class="stat-number"><?php echo esc_html($stats_data['most_popular_group'] ?? 'N/A'); ?></div></div>
        </div>

        <div id="dsa-yearly-chart-wrapper" style="margin-top: 30px; background: #fff; padding: 20px; border: 1px solid #ccd0d4;">
            <h2>Lessons per Month for <?php echo esc_html($selected_year); ?></h2>
            <canvas id="dsaYearlyChart"></canvas>
        </div>
        
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof Chart !== 'undefined' && document.getElementById('dsaYearlyChart')) {
                    const ctx = document.getElementById('dsaYearlyChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                            datasets: [{
                                label: 'Number of Lessons',
                                data: <?php echo json_encode($stats_data['chart_data'] ?? array_fill(0, 12, 0)); ?>,
                                backgroundColor: 'rgba(52, 152, 219, 0.5)',
                                borderColor: 'rgba(52, 152, 219, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: { scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
                    });
                }
            });
        </script>
    </div>
    <?php
}