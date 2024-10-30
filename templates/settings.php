<div class="wrap">
    <h2>Kaje Picture Password</h2>
    <form method="post" action="options.php"> 
        <?php @settings_fields('kaje_picture_password-group'); ?>
        <?php @do_settings_fields('kaje_picture_password-group'); ?>
        <?php do_settings_sections('kaje_picture_password'); ?>
        <?php @submit_button(); ?>
    </form>
</div>