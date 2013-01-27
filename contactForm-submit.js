$('#fat-btn').click(function () {

    // On the submit buttons click event
    // Assign the button and the output div to variables
    var btn = $(this);
    var out = $('#output');

    // Initialise the dataString
    var dataString = 'submit=submit';
    // Get every input in the contactForm
    var $inputs    = $('#contactForm :input');

    // For each input
    $inputs.each(function() {
        // Throw the name and the value into dataString
        dataString = dataString + '&'  + this.name + '=' + $(this).val();
    });

    // Do some wonderful bootstrap magic
    btn.button('loading');

    // Make a call via ajax to submit.php
    // Send data over POST
    // Times out after 30 seconds
    // Return json
    var xhr = $.ajax({
        type: 'POST',
        url: 'submit.php',
        data: dataString,
        dataType: 'json',
        timeout: 30000,
        success: function(response) {
            // We have a reply from the submit page,
            // it may be good, it may be bad, but we have a reply.
            if ( response['status'] == 'error' ) {
                // Response is an error, tell the user
                //
                // Change the buttons class and text
                btn.removeClass('btn-primary')
                    .addClass('btn-danger')
                    .text('Submit Error');

                // Show the error alert
                $('#errorDisplay').show().
                    text(response['errorText']);

                // Call the reset function
                resetTheForm();
            } else {
                // Repsonse is not an error

                // Change the buttons class and text
                btn.removeClass('btn-primary')
                    .addClass('btn-success')
                    .text('Success!');
            }

            // --- DEBUG
            // Interpret the json for debugging purposes
            $('#myTable').find('tbody').empty();
            $.each (response, function (bb) {
                $('#myTable').show();
                $('#myTable').find('tbody').append(
                    $('<tr>').append(
                        $('<td>').text(bb)
                    ).append(
                        $('<td>').append(
                            $('<pre>').text(response[bb])
                        )
                    )
                );
            });
            // --- END DEBUG

        },
        error: function(x, t, m) {
            // We didn't get a reply from the submit page, did the request time out
            if(t==='timeout') {
                btn.removeClass('btn-primary')
                    .addClass('btn-warning')
                    .text('Req time out');
            } else {
                btn.removeClass('btn-primary')
                    .addClass('btn-danger')
                    .text('Submit Error');
            }

            resetTheForm();
        }
    });
})

function resetTheForm() {
    // Set a time out
    setTimeout(function () {
        // Reset the buttons class and text
        $('#fat-btn').button('reset')
            .removeClass('btn-danger')
            .removeClass('btn-warning')
            .addClass('btn-primary');

        // Hide the alert box
        $('#errorDisplay').hide('slow');

        // Reload the catpcha
        Recaptcha.reload();
    // Set the timeout for 7 seconds
    }, 7000);
}