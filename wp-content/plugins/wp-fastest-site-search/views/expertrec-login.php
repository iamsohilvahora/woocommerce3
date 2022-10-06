<!DOCTYPE html>
<html lang="en">
<body class="expertrec-plugin-body">
<div class="expertrec-login-root">
    <div class="expertrec-box-root expertrec-flex-flex expertrec-flex-direction--column" style="min-height: 100vh;flex-grow: 1;">
        <div class="expertrec-loginbackground box-background--white padding-top--64">
            <div class="expertrec-loginbackground-gridContainer">
                <div class="expertrec-box-root expertrec-flex-flex" style="grid-area: top / start / 8 / end;">
                    <div class="expertrec-box-root" style="background-image: linear-gradient(white 0%, rgb(247, 250, 252) 33%); flex-grow: 1;">
                    </div>
                </div>
                <div class="expertrec-box-root expertrec-flex-flex" style="grid-area: 4 / 2 / auto / 5;">
                    <div class="expertrec-box-root box-divider--light-all-2 animationLeftRight tans3s" style="flex-grow: 1;"></div>
                </div>
                <div class="expertrec-box-root expertrec-flex-flex" style="grid-area: 6 / 5 / auto / 2;">
                    <div class="expertrec-box-root box-background--gray100 animationRightLeft" style="flex-grow: 1;"></div>
                </div>
                <div class="expertrec-box-root expertrec-flex-flex" style="grid-area: 7 / start / auto / 4;">
                    <div class="expertrec-box-root box-background--orange animationLeftRight" style="flex-grow: 1;"></div>
                </div>
                <div class="expertrec-box-root expertrec-flex-flex" style="grid-area: 8 / 4 / auto / 6;">
                    <div class="expertrec-box-root box-background--gray100 animationLeftRight tans3s" style="flex-grow: 1;"></div>
                </div>
                <div class="expertrec-box-root expertrec-flex-flex" style="grid-area: 2 / 15 / auto / end;">
                    <div class="expertrec-box-root box-divider--light-all-2 animationRightLeft tans4s" style="flex-grow: 1;"></div>
                </div>
                <div class="expertrec-box-root expertrec-flex-flex" style="grid-area: 3 / 14 / auto / end;">
                    <div class="expertrec-box-root box-background--orange animationRightLeft" style="flex-grow: 1;"></div>
                </div>
                <div class="expertrec-box-root expertrec-flex-flex" style="grid-area: 4 / 17 / auto / 20;">
                    <div class="expertrec-box-root box-background--gray100 animationRightLeft tans4s" style="flex-grow: 1;"></div>
                </div>
                <div class="expertrec-box-root expertrec-flex-flex" style="grid-area: 5 / 14 / auto / 17;">
                    <div class="expertrec-box-root box-divider--light-all-2 animationRightLeft tans3s" style="flex-grow: 1;"></div>
                </div>
            </div>
        </div>
        <div class="expertrec-box-root padding-top--24 expertrec-flex-flex expertrec-flex-direction--column" style="flex-grow: 1; z-index: 9;">
            <div class="expertrec-box-root padding-top--48 padding-bottom--24 expertrec-flex-flex flex-justifyContent--center">
                <h1 class="exp"><a class="exp" href="https://www.expertrec.com/">WP Fastest Site Search</a></h1>
            </div>
            <div class="formbg-outer">
                <div class="expertrec-formbg">
                    <div class="formbg-inner padding-horizontal--48">
                        <span class="exp padding-bottom--15">Sign in to your account</span>
                        <form class="exp">
                            <div class="exp-field padding-bottom--24">
                                <label class="exp" for="crawl_site_url">Site URL</label>
                                <input class="exp" type="crawl_site_url" name="crawl_site_url" placeholder="Enter Your Wordpress Site URL"
                                       value="<?php echo esc_attr(get_site_url()); ?>">
                            </div>
                            <?php include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
                                  $is_woocommerce = is_plugin_active( 'woocommerce/woocommerce.php' ); ?>
                            <div class="exp-field padding-bottom--24" <?php if($is_woocommerce) echo("hidden") ?>>
                                <label class="exp" for="radio_btn" style="margin-bottom: 24px;">Select Indexing Option (<a style="color: blue;" href="https://blog.expertrec.com/which-indexing-to-use-in-expertrec/" target="_blank">Need help?</a>)</label>
                                <section type="radio_btn">
                                    <div>
                                        <input type="radio" class="expertrec-radio-btn" id="dbWay" name="indexingWay" value="db" checked onclick="hideCrawlMsg()">
                                        <label class="radio-label exp-font--12" for="dbWay">
                                            <h2>Real Time Data Sync</h2>
                                            <p class="exp-radio-p">(Faster)</p>
                                        </label>
                                        <h4 class="expertrec-recommended">Recommended</h4>
                                    </div>
                                    <div>
                                        <input class="expertrec-radio-btn" type="radio" id="crawlWay" name="indexingWay" value="crawl" onclick="showCrawlMsg()">
                                        <label class="radio-label exp-font--12" for="crawlWay">
                                            <h2>Periodic Data Sync</h2>
                                            <p class="exp-radio-p">(Supports pdf, doc, xlsx)</p>
                                        </label>
                                    </div>
                                </section>
                            </div>
                        </form>
                        <div class="exp-field padding-bottom--24">
                            <!-- <button id="open-child-window"><span>Continue</span></button> -->
                            <input id="open-child-window" type="submit" name="submit" value="Continue">
                        </div>
                    </div>
                </div>
                <div class="footer-link padding-top--24">
                    <div class="listing padding-top--24 padding-bottom--24 expertrec-flex-flex expertrec-center-center">
                        <span class="exp"><a href="https://www.expertrec.com/wordpress-search-plugin/" target="_blank">© Expertrec</a></span>
                        <span class="exp"><a href="mailto:support@expertrec.com" target="_blank">Contact</a></span>
                        <span class="exp"><a href="https://www.expertrec.com/privacy-policy/" target="_blank">Privacy & terms</a></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div>
    <?php include("expertrec-page-footer.php") ?>
</div>
<script type="text/javascript">
    function hideCrawlMsg() {
        let crawlSelectionMsg = document.getElementById('crawlSelectionMsg');
        if(crawlSelectionMsg) {
            crawlSelectionMsg.style.display = "none";
        }
    }

    function showCrawlMsg() {
        let crawlSelectionMsg = document.getElementById('crawlSelectionMsg');
        if(crawlSelectionMsg) {
            crawlSelectionMsg.style.display = "block";
        }
    }
    hideCrawlMsg();
</script>
</body>
</html>
