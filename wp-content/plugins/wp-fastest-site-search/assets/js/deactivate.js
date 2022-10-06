jQuery(document).ready(function($) {

    var deactivateURL = "#";

    if (window.location.href.indexOf("/plugins.php") > -1) {

        const element = $('<div></div>')
        element.attr('id', 'expertrec_feedback_form');
        $('body').append(element)
        $('#expertrec_feedback_form').load(expertrecPath.pluginsUrl + '/views/deactivate.html');

        // Listening for deactivation
        $("#the-list [data-slug='wp-fastest-site-search'] .deactivate>a").on("click", function(event) {
            deactivateURL = event.target.href;
            event.preventDefault();
            showDeactivateForm();
        });

        const showDeactivateForm = function() {

            document.querySelector(".expertrec-modal").classList.toggle("expertrec-show-modal");

            const modal = document.querySelector(".expertrec-modal");

            function toggleModal() {
                modal.classList.toggle("expertrec-show-modal");
            }

            function windowOnClick(event) {
                if (event.target === modal) {
                    toggleModal();
                }
            }

            window.addEventListener("click", windowOnClick);


        }
    }

    window.expertrec_deactivate_close = function() {
        const modal = document.querySelector(".expertrec-modal");
        modal.classList.toggle("expertrec-show-modal");
    }

    window.expertrec_deactivate = function() {
        console.debug('Deactivate URL is: ', deactivateURL)
        console.debug("Selected value: ", document.querySelector(".expertrec-deactivate-select").value)
        var dropdown = document.querySelector(".expertrec-deactivate-select");
        var value = dropdown.options[dropdown.selectedIndex].value
        var text = dropdown.options[dropdown.selectedIndex].text
        var textarea = document.querySelector(".expertrec-deactivate-input")
        var description = textarea.value
        if(!value) {
            alert("Please select a value from dropdown.");
            return;
        } else if (text == 'Other' && !description) {
            alert("Please describe your experience.");
            return;
        }
        // Disabling the deactivate button
        document.querySelector(".expertrec-deactivate-btn").disabled = true;
        var data = {
            action: 'expertrec_deactivation',
            value: value,
            selected_option: text,
            description: description
        };
        $.post(the_ajax_script.ajaxurl, data, function(response) {
            window.open(deactivateURL, '_self');
        });
    }

})
