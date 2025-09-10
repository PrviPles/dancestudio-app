jQuery(document).ready(function($) {
    'use strict';

    /**
     * Logic for the 'Private Lesson' post type editor screen.
     */
    function initializePrivateLessonMetaBox() {
        const studentDropdown = $('#dsa_lesson_student1_id');
        const orderDropdown = $('#dsa_lesson_order_id');
        const spinner = $('#dsa-order-linking-wrapper .spinner');

        // Check if the necessary elements exist on the page
        if (!studentDropdown.length || !orderDropdown.length) {
            return;
        }

        const initiallySelectedOrderId = orderDropdown.data('selected-order-id');

        function fetchOrders(studentId) {
            if (!studentId || studentId <= 0) {
                orderDropdown.html('<option value="0">Please select a student first</option>').prop('disabled', true);
                return;
            }
            spinner.addClass('is-active');
            orderDropdown.prop('disabled', true).html('<option value="0">Loading packages...</option>');

            $.post(ajaxurl, {
                action: 'dsa_get_student_orders',
                nonce: dsaMetaBoxData.get_orders_nonce, // Nonce passed from PHP
                student_id: studentId
            }).done(function(response) {
                orderDropdown.empty(); 
                if (response.success && response.data.orders.length > 0) {
                    orderDropdown.append('<option value="0">-- Select a Package --</option>');
                    $.each(response.data.orders, function(i, order) {
                        const selectedAttr = (order.id == initiallySelectedOrderId) ? ' selected="selected"' : '';
                        orderDropdown.append('<option value="' + order.id + '"' + selectedAttr + '>' + order.text + '</option>');
                    });
                    orderDropdown.prop('disabled', false);
                } else {
                    orderDropdown.append('<option value="0">No packages found</option>');
                }
            }).fail(function() {
                orderDropdown.html('<option value="0">Error loading packages</option>');
            }).always(function() {
                spinner.removeClass('is-active');
            });
        }

        // Fetch orders on page load if a student is already selected
        if (studentDropdown.val() > 0) {
            fetchOrders(studentDropdown.val());
        }

        // Fetch orders when the student dropdown changes
        studentDropdown.on('change', function() {
            fetchOrders($(this).val());
        });
    }

    /**
     * Logic for the 'Group Class' post type editor screen.
     */
    function initializeGroupClassMetaBox() {
        const attendanceTable = $('.dsa-attendance-table');
        if (!attendanceTable.length) {
            return;
        }

        function updateCounts() {
            let attended = 0;
            const total = attendanceTable.find('tbody tr').length;
            attendanceTable.find('input[type=checkbox]:checked').each(function() {
                attended++;
            });
            const percent = total > 0 ? Math.round((attended / total) * 100) : 0;
            $('#dsa-attended-count').text(attended);
            $('#dsa-attendance-percentage').text(percent);
        }

        // Update counts when a checkbox changes
        attendanceTable.on('change', 'input[type=checkbox]', updateCounts);
        
        // Initial count on page load
        updateCounts();
    }

    // Run the correct function based on which admin screen we are on
    if ($('body').hasClass('post-type-dsa_private_lesson')) {
        initializePrivateLessonMetaBox();
    }

    if ($('body').hasClass('post-type-dsa_group_class')) {
        initializeGroupClassMetaBox();
    }
});