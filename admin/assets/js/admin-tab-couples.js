jQuery(document).ready(function($) {
    'use strict';

    if (typeof dsaCouplesData === 'undefined') {
        console.error('DSA Error: Couples data object is missing.');
        return;
    }

    $('.dsa-student-search-select').select2({
        ajax: {
            url: ajaxurl,
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    action: 'dsa_search_students',
                    nonce: dsaCouplesData.nonce,
                    term: params.term
                };
            },
            processResults: function (data, params) {
                // This safety check prevents crashes if the server returns an error or incorrect format.
                if (data && data.success && data.data && Array.isArray(data.data.results)) {
                    return {
                        results: data.data.results
                    };
                }
                // If the data is bad, return an empty list and log the issue.
                console.error('Received invalid data from server for student search:', data);
                return {
                    results: []
                };
            },
            transport: function (params, success, failure) {
                var $request = $.ajax(params);

                $request.fail(function(jqXHR, textStatus, errorThrown) {
                    alert("A critical server error occurred while searching. Please check your browser's console for more details.");
                    failure(jqXHR, textStatus, errorThrown);
                });

                $request.done(function(data) {
                    if (data.success) {
                        success(data);
                    } else {
                        var errorMessage = data.data.message || 'An unknown error occurred on the server.';
                        alert('Could not search for students. Server responded: ' + errorMessage);
                        failure(data);
                    }
                });

                return $request;
            },
            cache: true
        },
        minimumInputLength: 2,
        placeholder: 'Type to search for a student...'
    });
});