jQuery(document).ready(function($) {

    var checkIndexingIntervalID;

    if (window.location.href.indexOf("?page=Expertrec") > -1) {
        // Login/Home Page loaded

        if ($('#open-child-window').length) {
            // Login Page
            console.debug('Login Page Loaded.')
            // variable that holds the handle of the child
            let child_window_handle = null;

            // on opening child window
            document.querySelector("#open-child-window").addEventListener('click', function() {
                child_window_handle = window.open('https://wordpress.expertrec.com/createorg.html', '_blank');
            });


            // event handler will listen for messages from the child
            window.addEventListener('message', function(e) {
                // e.data hold the message from child
                const message = e.data
                console.debug("to parent : ", message); 
                if (message === 'send_data') {
                    sendDataToChild();
                } else if ('final_data' in message) {
                    console.debug('final data from the child is :: ', message)
                    console.debug('type is ', typeof(message))
                    var data = {
                      action: 'expertrec_login_response',
                      site_id: message.final_data.site_id,
                      ecom_id: message.final_data.ecom_id,
                      cse_id: message.final_data.cse_id,
                      write_api_key: message.final_data.write_api_key,
                      expertrec_engine: message.final_data.expertrec_engine
                    };
                    // the_ajax_script.ajaxurl is a variable that will contain the url to the ajax processing file
                    $.post(the_ajax_script.ajaxurl, data, function(response) {
                      console.debug(response);
                      child_window_handle.close()
                      location.reload();
                    });
                }
            } , false);

            function sendDataToChild() {
                var d = {
                    action: 'expertrec_get_site_info'
                }
                $.post(the_ajax_script.ajaxurl, d, function(response) {
                    var resp = JSON.parse(response)
                    console.debug("Site info for login: ", resp)
                    resp["site_url"] = jQuery("input[name='crawl_site_url']:input").val()
                    resp["expertrec_engine"] = jQuery("input[name='indexingWay']:checked").val()
                    console.debug("Sending Data: ", resp)
                    // this will post a message to the child
                    child_window_handle.postMessage(resp, "*");
                });
            }
        } else {
            // Home Page
            console.debug('Home Page Loaded.')

            var data = {
                action: 'expertrec_engine'
            }
            $.post(the_ajax_script.ajaxurl, data, function(response) {
                console.debug("Expertrec engine: ", response)
                var resp = JSON.parse(response)
                if (resp=="db") {
                    // If Engine = db, Load this JS

                    console.debug("Update index stats")
                    // Updating indexing progress stats on loading home page
                    update_index_stats()
                    jQuery("#expertrec-reindex-btn").hide()
                    // Update indexing progress every 2 sec, if indexing is not completed
                    checkIndexingIntervalID = setInterval(check_indexing_status, 2000);

                    // When first time home page loaded, then starting re-indexing
                    var data = {
                        action: 'expertrec_account_created'
                    }
                    $.post(the_ajax_script.ajaxurl, data, function(response) {
                        var json_data = JSON.parse(response)
                        console.debug("On loading home page: ", json_data)
                        if ( json_data.account_created && !json_data.first_sync_done ) {
                            start_indexing();
                        }
                    })

                    // Getting last successful sync time
                    var data = {
                        action: 'expertrec_last_sync'
                    }
                    $.post(the_ajax_script.ajaxurl, data, function(response) {
                        console.debug("last sync: ", response)
                        if (response == 'NA') {
                            jQuery(".expertrec-last-sync").hide()
                        } else {
                            var date = new Date(response * 1000)
                            jQuery("#exp-sync-time").text(date.toLocaleString())
                        }
                    })

                    // Re-Index Button functionality
                    jQuery("#expertrec-reindex-btn").click(function(event) {
                        console.debug("Clicked reindex button")
                        jQuery("#exp-reindex-loading").css('display', 'block')
                        jQuery("#expertrec-reindex-btn").hide()
                        // First set the indexing progress to 0, then start indexing
                        reset_index_stats()
                        jQuery('.expertrec-svg-circle-product').animate({'stroke-dashoffset': 386}, 1000)
                        jQuery('.expertrec-svg-circle-post').animate({'stroke-dashoffset': 386}, 1000)
                        jQuery('.expertrec-svg-circle-page').animate({'stroke-dashoffset': 386}, 1000)
                        start_indexing()
                    });

                    // Stop-Indexing Button functionality
                    jQuery("#expertrec-stop-indexing-btn").click(function(event) {
                        console.debug("Clicked stop indexing button.")
                        jQuery("#expertrec-stop-indexing-btn").hide()
                        var data = {
                            action: 'expertrec_stop_indexing'
                        }
                        $.post(the_ajax_script.ajaxurl, data, function(response) {
                            console.debug("Stop indexing response", response)
                        })
                    })
                } else {
                    // If Engine = crawl, then load this JS

                    // Getting page crawl status
                    var data = {
                        action: 'expertrec_crawl',
                        func_to_call: 'crawl_status'
                    }
                    $.post(the_ajax_script.ajaxurl, data, function(response) {
                        console.debug("Crawl Status: ", response)
                        var crawl_input = JSON.parse(response)
                        if (crawl_input) {
                            var status = crawl_input.crawl_status
                            if (status=="") {
                                status = "NA"
                            }
                            var pages_crawled = crawl_input.pages_crawled
                            jQuery('#exp-pages-crawled').text(pages_crawled)
                            jQuery('#exp-crawl-status').text(status)
                        }
                    })

                    // Re-crawl button
                    jQuery("#expertrec-recrawl-btn").click(function(event) {
                        console.debug("Re-Crawl btn clicked")
                        jQuery("#expertrec-recrawl-btn").hide()
                        jQuery("#expertrec-stop-crawl-btn").show()
                        var data = {
                            action: 'expertrec_crawl',
                            func_to_call: 'start_crawl'
                        }
                        $.post(the_ajax_script.ajaxurl, data, function(response) {
                            console.debug('Re-Crawl response: ', response)
                            jQuery("#expertrec-stop-crawl-btn").hide()
                            jQuery("#expertrec-recrawl-btn").show()
                        })
                    })

                    // Stop crawl button
                    jQuery("#expertrec-stop-crawl-btn").click(function(event) {
                        console.debug("Stop Crawl btn clicked")
                        jQuery("#expertrec-stop-crawl-btn").hide()
                        jQuery("#expertrec-recrawl-btn").show()
                        var data = {
                            action: 'expertrec_crawl',
                            func_to_call: 'stop_crawl'
                        }
                        $.post(the_ajax_script.ajaxurl, data, function(response) {
                            console.debug('Re-Crawl response: ', response)
                        })
                    })
                }
            })

            // Install mode settings update
            jQuery("#search-hook-val").click(function(event) {
                console.debug("Install mode upadte btn clicked.")
                jQuery("#exp-install-loading").css('display','block')
                jQuery("#search-hook-val").hide()
                var hook = jQuery("input[name='searchHook']:checked").val()
                var hook_val = true
                if (hook == 'expertrec') {
                    hook_val = false
                }
                data = {
                    action: 'expertrec_update_config',
                    data: {
                        hook_on_existing_input_box: hook_val,
                        org_status: 'NA'
                    },
                    update_type: 'install_mode'
                }
                $.post(the_ajax_script.ajaxurl, data, function(response) {
                    console.debug(response)
                    jQuery("#exp-install-loading").css('display','none')
                    jQuery("#search-hook-val").show()
                })
            })

            // Trial days
            var data = {
                action: 'expertrec_is_expired'
            }
            $.post(the_ajax_script.ajaxurl, data, function(response) {
                var json_data = JSON.parse(response)
                console.debug("Trial days response: ", json_data.days, json_data.is_subscribed)
                if (json_data) {
                    var isExpire = 15;
                    if (json_data.is_subscribed) {
                        jQuery(".search-progress-cu-outer").hide()
                    } else {
                        var original_days = json_data.days + " days left in trial";
                        var search_class = ''
                        if ( json_data.days<16 && json_data.days>6 ) {
                            search_class = 'green_section_color'
                        } else if ( json_data.days<7 && json_data.days>=1 ) {
                            search_class = 'orange_section_color'
                        } else {
                            search_class = 'red_section_color'
                            original_days = 'Your trial period is expired.'
                            isExpire = 0;
                        }
                        jQuery(".expertrec_search_wrap").addClass('expertrec-trial')
                        jQuery(".search-progress-count").addClass(search_class)
                        jQuery(".search-progress-count").text(original_days)
                    }
                }
            })
        }


        function reset_index_stats() {
            // This will reset the indexing progress to 0
            var data = {
                action: 'expertrec_reset_indexing_progress'
            }
            $.post(the_ajax_script.ajaxurl, data, function(response) {
                console.debug("Reset indexing progress response: ", response)
            })
        }


        function check_indexing_status() {
            var data = {
                action: 'expertrec_indexing_status'
            }
            $.post(the_ajax_script.ajaxurl, data, function(response) {
                console.debug("Indexing Status: ", response)
                if (response == 'indexing') {
                    jQuery("#exp-reindex-loading").css('display', 'block')
                    jQuery("#expertrec-reindex-btn").hide()
                    jQuery("#expertrec-stop-indexing-btn").show()
                    update_index_stats()
                } else {
                    jQuery("#exp-reindex-loading").css('display', 'none')
                    jQuery("#expertrec-stop-indexing-btn").hide()
                    jQuery("#expertrec-reindex-btn").show()
                    clearInterval(checkIndexingIntervalID);
                    update_index_stats()
                }
            });
        }


        function update_index_stats() {
            console.debug("update_index_stats")
            var data = {
                action: 'expertrec_index_stats'
            }
            $.post(the_ajax_script.ajaxurl, data, function(response) {
                var json_data = JSON.parse(response)
                console.debug("Index stats: ", json_data)
                var prod_indexed_percent = (386/json_data.product.indexable) * json_data.product.indexed
                var page_indexed_percent = (386/json_data.page.indexable) * json_data.page.indexed
                var post_indexed_percent = (386/json_data.post.indexable) * json_data.post.indexed
                console.debug("products indexed: ", json_data.product.indexable)
                console.debug("percent_completed product: ", prod_indexed_percent)
                console.debug("percent_completed page: ", page_indexed_percent)
                console.debug("percent_completed post: ", post_indexed_percent)
                jQuery('.expertrec-svg-circle-product').animate({'stroke-dashoffset': 386-prod_indexed_percent}, 1000)
                jQuery('#exp-indexed-prod').text(json_data.product.indexed+' / '+json_data.product.indexable)
                jQuery('.expertrec-svg-circle-post').animate({'stroke-dashoffset': 386-post_indexed_percent}, 1000)
                jQuery('#exp-indexed-post').text(json_data.post.indexed+' / '+json_data.post.indexable)
                jQuery('.expertrec-svg-circle-page').animate({'stroke-dashoffset': 386-page_indexed_percent}, 1000)
                jQuery('#exp-indexed-page').text(json_data.page.indexed+' / '+json_data.page.indexable)
            })
        }


        function start_indexing() {
            console.debug("start_indexing called")
            jQuery("#exp-reindex-loading").css('display', 'block')
            jQuery("#expertrec-reindex-btn").hide()
            jQuery("#expertrec-stop-indexing-btn").show()
            checkIndexingIntervalID = setInterval(check_indexing_status, 2000);
            var data = {
                action: 'expertrec_reindex_data'
            }
            $.post(the_ajax_script.ajaxurl, data, function(response) {
                console.debug("Indexing Completed")
                jQuery("#expertrec-stop-indexing-btn").hide()
                jQuery("#expertrec-reindex-btn").show()
                clearInterval(checkIndexingIntervalID);
                location.reload()
            });
        }

    } else if (window.location.href.indexOf("?page=expertrecsearch-layout") > -1) {
        // Layout Page

        jQuery('#layout-update-btn').click(function(event) {
            // To prevent default form submission
            event.preventDefault()
            console.debug("Clicked layout update btn.")
            jQuery('#exp-layout-loading').css('display', 'block')
            jQuery('#layout-update-btn').hide()
            var temp = jQuery("input[name='template']:checked").val()
            var data = {
                action: 'expertrec_layout_submit',
                template: temp
            }
            if (temp=='separate') {
                jQuery.extend(data, {
                    search_path: jQuery("input[name='search_path']").val(),
                    query_parameter: jQuery("input[name='query_parameter']").val()
                })
            }
            $.post(the_ajax_script.ajaxurl, data, function(response) {
                console.debug("layout update response: ", response)
                jQuery('#layout-update-btn').show()
                jQuery('#exp-layout-loading').css('display', 'none')
            })
        })
    } else if (window.location.href.indexOf("?page=expertrecsearch-settings")) {
        // Settings Page

        jQuery('#settings-update-btn').click(function(event) {
            // To prevent default form submission
            event.preventDefault()
            console.debug("Clicked settings update btn.")
            jQuery("#exp-settings-loading").css('display', 'block')
            jQuery('#settings-update-btn').hide()
            var exp_eng = jQuery("input[name='indexingWay']:checked").val()
            var data = {
                action: 'expertrec_settings_update',
                engine: exp_eng
            }
            $.post(the_ajax_script.ajaxurl, data, function(response) {
                response = JSON.parse(response)
                console.debug("Settings update response: ", response)
                jQuery("input[name=api_key]").val(response)
                jQuery('#settings-update-btn').show()
                jQuery("#exp-settings-loading").css('display', 'none')
            })
        })
    }

})
