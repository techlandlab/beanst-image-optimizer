<?php
if (!defined('ABSPATH')) exit;

$settings = get_option('beanst_optimizer_settings');
$unconverted = BeanST_Bulk::get_unconverted_count();
?>

<div class="wrap">
    <h1>🚀 BeanST Image Optimizer</h1>
    
    <div class="beanst-stats-card">
        <h2>📊 Statistics</h2>
        <p><strong><?php echo $unconverted; ?></strong> images need conversion</p>
        
        <button id="beanst-bulk-convert" class="button button-primary button-large">
            Start Bulk Conversion
        </button>
        
        <div id="beanst-progress" style="display:none; margin-top:20px;">
            <progress id="beanst-progress-bar" max="100" value="0" style="width:100%;"></progress>
            <p id="beanst-progress-text">Processing...</p>
        </div>
    </div>
    
    <form method="post" action="options.php">
        <?php settings_fields('beanst_optimizer'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">Enable WebP</th>
                <td>
                    <input type="checkbox" name="beanst_optimizer_settings[enable_webp]" 
                           value="1" <?php checked($settings['enable_webp'], 1); ?>>
                </td>
            </tr>
            
            <tr>
                <th scope="row">WebP Quality</th>
                <td>
                    <input type="range" name="beanst_optimizer_settings[quality_webp]" 
                           min="60" max="100" value="<?php echo $settings['quality_webp']; ?>"
                           oninput="this.nextElementSibling.value = this.value">
                    <output><?php echo $settings['quality_webp']; ?></output>
                </td>
            </tr>
            
            <tr>
                <th scope="row">Enable AVIF</th>
                <td>
                    <input type="checkbox" name="beanst_optimizer_settings[enable_avif]" 
                           value="1" <?php checked($settings['enable_avif'], 1); ?>>
                </td>
            </tr>
            
            <tr>
                <th scope="row">AVIF Quality</th>
                <td>
                    <input type="range" name="beanst_optimizer_settings[quality_avif]" 
                           min="60" max="100" value="<?php echo $settings['quality_avif']; ?>"
                           oninput="this.nextElementSibling.value = this.value">
                    <output><?php echo $settings['quality_avif']; ?></output>
                </td>
            </tr>
            
            <tr>
                <th scope="row">Auto-convert on upload</th>
                <td>
                    <input type="checkbox" name="beanst_optimizer_settings[auto_convert]" 
                           value="1" <?php checked($settings['auto_convert'], 1); ?>>
                </td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    </form>
</div>
