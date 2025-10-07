jQuery(document).ready(function($) {
    'use strict';

    if (typeof dsaMetaBoxData === 'undefined') {
        return;
    }

    /**
     * This function handles logic for the Private Lesson editor screen.
     */
    function initializePrivateLessonMetaBox() {
        // --- Logic for fetching WooCommerce orders (unchanged) ---
        const studentDropdown = $('#dsa_lesson_student1_id');
        const orderDropdown = $('#dsa_lesson_order_id');
        const orderSpinner = $('#dsa-order-linking-wrapper .spinner');

        if (studentDropdown.length && orderDropdown.length) {
            const initiallySelectedOrderId = orderDropdown.data('selected-order-id');

            function fetchOrders(studentId) {
                if (!studentId || studentId <= 0) {
                    orderDropdown.html('<option value="0">Please select a student first</option>').prop('disabled', true);
                    return;
                }
                orderSpinner.addClass('is-active');
                orderDropdown.prop('disabled', true).html('<option value="0">Loading packages...</option>');

                $.post(ajaxurl, {
                    action: 'dsa_get_student_orders',
                    nonce: dsaMetaBoxData.get_orders_nonce,
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
                    orderSpinner.removeClass('is-active');
                });
            }

            if (studentDropdown.val() > 0) {
                fetchOrders(studentDropdown.val());
            }

            studentDropdown.on('change', function() {
                fetchOrders($(this).val());
            });
        }


        // --- NEW: Logic for fetching Dance Figures ---
        const danceDropdown = $('#dsa_practiced_dance');
        const figuresWrapper = $('#dsa-figures-checklist-wrapper');
        const figuresSpinner = figuresWrapper.next('.spinner');
        const lessonId = $('#post_ID').val();

        danceDropdown.on('change', function() {
            const danceTermId = $(this).val();

            if (!danceTermId || danceTermId <= 0) {
                figuresWrapper.html('<em>' + 'Please select a dance above.' + '</em>');
                return;
            }

            figuresSpinner.addClass('is-active');
            figuresWrapper.html(''); // Clear previous results

            $.post(ajaxurl, {
                action: 'dsa_get_figures_for_dance',
                nonce: dsaMetaBoxData.get_figures_nonce,
                dance_id: danceTermId,
                post_id: lessonId
            }).done(function(response) {
                if (response.success && response.data.length > 0) {
                    let checklistHtml = '';
                    response.data.forEach(function(figure) {
                        const isChecked = figure.practiced ? 'checked' : '';
                        const fieldId = 'dsa-figure-' + figure.id;
                        checklistHtml += `
                            <div style="margin-bottom: 5px;">
                                <input type="checkbox" name="dsa_practiced_figures[]" value="${figure.id}" id="${fieldId}" ${isChecked}>
                                <label for="${fieldId}" style="display: inline-block; margin-left: 5px;">${figure.title}</label>
                            </div>`;
                    });
                    figuresWrapper.html(checklistHtml);
                } else {
                    figuresWrapper.html('<em>' + 'No figures found for this dance.' + '</em>');
                }
            }).fail(function() {
                figuresWrapper.html('<em>' + 'Error loading figures.' + '</em>');
            }).always(function() {
                figuresSpinner.removeClass('is-active');
            });
        });
    }

    // --- Main Initializer ---
    if ($('body').hasClass('post-type-dsa_private_lesson')) {
        initializePrivateLessonMetaBox();
    }
});