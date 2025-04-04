<?php
echo ' ?>

<div class="wrap">
<h1><?php echo esc_html__('Live Foundation Dashboard', 'live-foundation'); ?></h1>
<div class="welcome-panel">
    <div class="welcome-panel-content">
        <h2><?php echo esc_html__('Welcome to Live Foundation!', 'live-foundation'); ?></h2>
        <p class="about-description"><?php echo esc_html__('A powerful foundation plugin for WordPress.', 'live-foundation'); ?></p>
        <div class="welcome-panel-column-container">
            <div class="welcome-panel-column">
                <h3><?php echo esc_html__('Get Started', 'live-foundation'); ?></h3>
                <a class="button button-primary button-hero" href="<?php echo admin_url('admin.php?page=live-foundation-settings'); ?>"><?php echo esc_html__('Configure Settings', 'live-foundation'); ?></a>
            </div>
            <div class="welcome-panel-column">
                <h3><?php echo esc_html__('Documentation', 'live-foundation'); ?></h3>
                <ul>
                    <li><a href="#" class="welcome-icon welcome-learn-more"><?php echo esc_html__('User Guide', 'live-foundation'); ?></a></li>
                    <li><a href="#" class="welcome-icon welcome-learn-more"><?php echo esc_html__('Developer Documentation', 'live-foundation'); ?></a></li>
                </ul>
            </div>
            <div class="welcome-panel-column welcome-panel-last">
                <h3><?php echo esc_html__('Support', 'live-foundation'); ?></h3>
                <ul>
                    <li><a href="#" class="welcome-icon welcome-support-forum"><?php echo esc_html__('Support Forum', 'live-foundation'); ?></a></li>
                    <li><a href="#" class="welcome-icon welcome-support-contact"><?php echo esc_html__('Contact Us', 'live-foundation'); ?></a></li>
                </ul>
            </div>
        </div>
    </div>
</div>
</div>
<?php '; ?>