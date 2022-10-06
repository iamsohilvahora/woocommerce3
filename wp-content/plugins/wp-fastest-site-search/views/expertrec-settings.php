<!DOCTYPE html>
<html lang="en">
<head>
    <style type="text/css">

.expertrec-form {
  display: block;
  position: absolute;
  left: 45%;
  transform: translateX(-50%);
  margin: 100px auto;
  width: 28rem;
}
#expertrec-input-noneditable {
    background: #F1F0F2;
    display: block;
    border: none;
    border-bottom: 1px solid #ccc;
    width: inherit;
}
        

    </style>
</head>
<body>
    <div>
        <?php include("expertrec-page-header.php") ?>
    </div>
    <form class="expertrec-form">
        <?php $options = get_option('expertrec_options'); $site_id = $options['site_id'];
            $write_key = $options['write_api_key']; $exp_eng = get_option('expertrec_engine'); ?>
        <h2>API Key: </h2>
        <input id="expertrec-input-noneditable" type="text" name="api_key" readonly value="<?php echo $site_id; ?>">
        <h2>Write Key: </h2>
        <input id="expertrec-input-noneditable" type="text" name="write_key" readonly value="<?php echo $write_key; ?>">
        <?php include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
              $is_woocommerce = is_plugin_active( 'woocommerce/woocommerce.php' );
        if (!$is_woocommerce) { ?>
        <h2>Indexing Way:</h2>
        <div>
            <section type="" style="margin-left: 10px; margin-right: 20px; margin-top: 45px;">
                <div>
                    <input class="expertrec-radio-btn" type="radio" id="dbWay" name="indexingWay" value="db" <?php if($exp_eng=='db'){ echo "checked=checked";} ?> >
                    <label class="radio-label exp-font--12" for="dbWay">
                        <h2>Real Time Data Sync</h2>
                        <p class="exp-radio-p">(Faster)</p>
                    </label>
                </div>
                <div>
                    <input type="radio" class="expertrec-radio-btn" id="crawlWay" name="indexingWay" value="crawl" <?php if($exp_eng!='db'){ echo "checked=checked"; } ?> >
                    <label class="radio-label exp-font--12" for="crawlWay">
                        <h2>Periodic Data Sync</h2>
                        <p class="exp-radio-p">(Supports pdf, doc, xlsx)</p>
                    </label>
                </div>
            </section>
        </div>
        <div class="exp-field padding-bottom--24">
            <img class="expertrec-loading-img" id="exp-settings-loading" src="<?php echo plugin_dir_url(__DIR__) . 'assets/images/Spin-1s-200px.svg' ?>" alt='loading' style="display: none;">
            <input class="expertrec-align-center" id="settings-update-btn" type="submit" name="submit" value="Update" style="width: 173px; margin-top: 24px; margin-bottom: 24px;">
        </div>
        <?php } ?>
    </form>
    <div>
        <?php include("expertrec-page-footer.php") ?>
    </div>
</body>
</html>