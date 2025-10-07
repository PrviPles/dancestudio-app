jQuery(document).ready(function($) {
    'use strict';
    
    if (typeof dsaManagerData === 'undefined' || typeof dsaManagerData.groups_nonce === 'undefined') {
        console.error('DSA Error: Manager data for groups is missing.');
        return;
    }

    // --- 1. "NEW GROUP" button click ---
    $('#dsa-new-group-button').on('click', function() {
        $('#dsa-new-group-modal').dialog({
            modal: true,
            width: 400,
            buttons: {
                "Create Group": function() {
                    $('#dsa-new-group-form').trigger('submit');
                },
                Cancel: function() {
                    $(this).dialog('close');
                }
            }
        });
    });

    // --- 2. CREATE New Group form submission ---
    $('#dsa-new-group-form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var groupName = form.find('#dsa_new_group_name').val();
        var modal = $('#dsa-new-group-modal');

        $.post(dsaManagerData.ajax_url, {
            action: 'dsa_create_group_ajax',
            nonce: dsaManagerData.groups_nonce,
            group_name: groupName
        }).done(function(response) {
            if (response.success) {
                $('#dsa-no-groups-row').remove();
                var newRow = $(response.data.html).hide();
                $('#dsa-all-groups-table tbody').append(newRow);
                newRow.fadeIn();
                modal.dialog('close');
            } else {
                alert('Error: ' + (response.data.message || 'Could not create group.'));
            }
        }).fail(function() {
            alert('A server error occurred.');
        });
    });

    // --- 3. "EDIT" button click ---
    $('#dsa-all-groups-table').on('click', '.dsa-edit-group-button', function() {
        var groupId = $(this).data('group-id');
        var groupName = $(this).data('group-name');

        $('#dsa_edit_group_name').val(groupName);
        $('#dsa_edit_group_id').val(groupId);
        
        $('#dsa-edit-group-modal').dialog({
            modal: true,
            width: 400,
            buttons: {
                "Save Changes": function() {
                    $('#dsa-edit-group-form').trigger('submit');
                },
                Cancel: function() {
                    $(this).dialog('close');
                }
            }
        });
    });

    // --- 4. EDIT Group form submission ---
    $('#dsa-edit-group-form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var groupId = form.find('#dsa_edit_group_id').val();
        var newGroupName = form.find('#dsa_edit_group_name').val();
        var modal = $('#dsa-edit-group-modal');

        $.post(dsaManagerData.ajax_url, {
            action: 'dsa_update_group_ajax',
            nonce: dsaManagerData.groups_nonce,
            group_id: groupId,
            group_name: newGroupName
        }).done(function(response) {
            if (response.success) {
                // Update the name in the table and the button's data attribute
                var row = $('#dsa-group-row-' + groupId);
                row.find('.dsa-group-name').text(newGroupName);
                row.find('.dsa-edit-group-button').data('group-name', newGroupName);
                modal.dialog('close');
            } else {
                alert('Error: ' + (response.data.message || 'Could not update group.'));
            }
        }).fail(function() {
            alert('A server error occurred.');
        });
    });

    // --- 5. "DELETE" button click ---
    $('#dsa-all-groups-table').on('click', '.dsa-delete-group-button', function(e) {
        e.preventDefault();
        var groupId = $(this).data('group-id');
        var row = $('#dsa-group-row-' + groupId);
        var groupName = row.find('.dsa-group-name').text();

        if (confirm('Are you sure you want to permanently delete the group "' + groupName + '"?')) {
            $.post(dsaManagerData.ajax_url, {
                action: 'dsa_delete_group_ajax',
                nonce: dsaManagerData.groups_nonce,
                group_id: groupId
            }).done(function(response) {
                if (response.success) {
                    row.fadeOut(400, function() { $(this).remove(); });
                } else {
                    alert('Error: ' + (response.data.message || 'Could not delete group.'));
                }
            }).fail(function() {
                alert('A server error occurred.');
            });
        }
    });
});